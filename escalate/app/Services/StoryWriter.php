<?php

namespace App\Services;

use App\Models\Desire;
use App\Models\Story;
use App\Models\User;
use App\Services\Ai\Anthropic;
use Illuminate\Support\Str;

/**
 * Turns a desire and a profile into a reading.
 *
 * The prompt is the product. Two rules shape all of it:
 *
 *   1. Present tense, already true. The user is not being told what they will
 *      have; they are being read a description of an ordinary day inside the
 *      life they described. That is the entire mechanism.
 *
 *   2. Specifics only. Every concrete noun the user gave us — a name, a street,
 *      a number, a habit — must appear. Generic manifestation prose is
 *      worthless, and the difference between "abundance flows to you" and "the
 *      47 is still sitting on the screen like a fact of the week" is the whole
 *      difference between this app and a fortune cookie.
 *
 * The contrast beat is non-negotiable and is called out in the rules below.
 * It is the emotional centre of a reading: naming what the user used to do,
 * and then not doing it.
 */
class StoryWriter
{
    public function __construct(private Anthropic $anthropic) {}

    public function write(Story $story): void
    {
        $user = $story->user;
        $desire = $story->desire;

        $text = $this->anthropic->write(
            $this->system($user, $desire),
            $this->user($user, $desire),
            $user,
            'story',
        );

        [$text, $declared] = $this->splitNames($text);

        // Refuse rather than store. A reading with a stranger in it is worse
        // than no reading: the whole promise is that this is the reader's own
        // life, and one invented brother costs more trust than a failure does.
        $this->refuseStrangers($declared, $this->peopleFor($user, $desire));

        $story->body = $this->tidy($text);
        $story->title = $this->titleFor($desire, $story->body);
        $story->word_count = str_word_count($story->body);
        $story->model = config('escalate.story.model');
        $story->state = 'ready';
        $story->save();
    }

    /**
     * Take the NAMES line off the end, and report what it claimed.
     *
     * Same shape as the (DESIRE n) tag in AffirmationWriter: a routing
     * instruction that must never survive into something a person reads.
     *
     * @return array{0: string, 1: array<int, string>}
     */
    private function splitNames(string $text): array
    {
        if (! preg_match('/^\s*\**NAMES\**\s*:\s*(.*)$/im', $text, $m, PREG_OFFSET_CAPTURE)) {
            // No line at all. Older behaviour, and not itself a reason to throw
            // away a piece — the allowlist check below simply has nothing to
            // check, and the prompt remains the thing doing the work.
            return [$text, []];
        }

        $prose = rtrim(substr($text, 0, $m[0][1]));

        $names = collect(preg_split('/[,;]+/', $m[1][0]))
            ->map(fn ($name) => trim($name, " \t\n\r\0\x0B\"'*.·-"))
            ->filter()
            ->reject(fn ($name) => in_array(mb_strtolower($name), ['none', 'nobody', 'no one', 'n/a'], true))
            ->values()
            ->all();

        return [$prose, $names];
    }

    /**
     * Throw if the piece named somebody the reader did not.
     *
     * A net, not a proof: a model that names a person and leaves them off its
     * own declaration still gets through, and no reliable way exists to find an
     * arbitrary invented name in prose. The prompt is the fix. This catches the
     * case where the model is honest and wrong, which — on the evidence of the
     * reading that started this — is the case that actually happens.
     *
     * @param  array<int, string>  $declared
     * @param  array<int, string>  $allowed
     */
    private function refuseStrangers(array $declared, array $allowed): void
    {
        $allowedLower = array_map('mb_strtolower', $allowed);

        $strangers = array_values(array_filter(
            $declared,
            fn ($name) => ! in_array(mb_strtolower($name), $allowedLower, true),
        ));

        if ($strangers !== []) {
            throw new \RuntimeException(
                'The reading named somebody the reader did not: '.implode(', ', $strangers),
            );
        }
    }

    /* ── prompts ─────────────────────────────────────────────────────────── */

    private function system(User $user, ?Desire $desire): string
    {
        $profile = $user->world();
        [$low, $high] = config("escalate.lengths.{$this->length($profile, $desire)}.words");

        /*
         * Stated at length, and with the failure spelled out, because the
         * short version lost.
         *
         * "Second person — you are. Address the reader directly." was one line
         * against rule 4's "every name must appear at least once, spelled
         * exactly as they wrote it" — and the reader's own name is in the
         * context block right under "Call them:". The model resolved the
         * conflict the way anyone would: it used the name, which forced the
         * whole piece into third person. Live output read "the water spots on
         * the glass Erika didn't dry last night. She leaves it there."
         *
         * That is not a reading. It is someone being narrated at. The entire
         * point is a person standing inside their own life, so the perspective
         * rule now names the wrong answer explicitly rather than trusting the
         * right one to win on its own.
         */
        $perspective = match ($desire?->perspective ?: $profile->perspective) {
            'second' => <<<'P'
            Second person, throughout. The reader is "you" — every single time.

               Right: "You leave it there. You stand a second longer than the coffee requires."
               Wrong: "Erika leaves it there. She stands a second longer."

               Never write the reader's name. Never write "she", "he" or "they"
               meaning the reader. If a sentence describes the reader from the
               outside, it is wrong — rewrite it from inside.
            P,
            default => <<<'P'
            First person, throughout. The reader is "I" — every single time.

               Right: "I leave it there. I don't check the balance twice anymore."
               Wrong: "Erika leaves it there. She doesn't check the balance twice anymore."

               Never write the reader's name. Never write "she", "he" or "they"
               meaning the reader. This is the reader speaking about their own
               life, in their own mouth. If a sentence describes them from the
               outside, it is wrong — rewrite it from inside.
            P,
        };

        $faith = $this->faithRule($profile->faith_language);
        $style = $this->styleRule($desire?->tone ?: $profile->tone, $profile->story_style);
        $naming = $this->namingRule($this->peopleFor($user, $desire));

        return <<<PROMPT
        You write manifestation readings: short pieces of prose that describe an
        ordinary moment inside a life the reader has already arrived at.

        NON-NEGOTIABLE RULES

        1. Present tense throughout. Never future, never conditional. Nothing
           "will" happen and nothing is "coming". It is already the case.
        2. {$perspective}
        3. Between {$low} and {$high} words. Prose paragraphs separated by blank
           lines. No headings, no lists, no titles, no markdown, no preamble —
           begin with the first sentence of the piece itself.
        4. Use the reader's own specifics. Every place, number and object they
           gave you must appear at least once, spelled exactly as they wrote it.

           {$naming}

           The reader's own name never appears in the prose, whatever else is
           allowed. It is only there so you know how to address them. Writing it
           drags the whole piece into third person, which breaks rule 2.
        5. Include one contrast beat, and place it near the middle: name a
           small, specific thing they used to do out of scarcity or fear, and
           then show them not doing it. Not as triumph — as something that
           simply did not occur to them today. This is the most important
           sentence in the piece.
        6. Undersell the ending. No crescendo, no lesson, no "and that is how
           I learned". End on something ordinary and physical: a cup set down,
           a door, the light moving. The calm is the point.
        7. {$faith}

        VOICE

        {$style}

        Write about texture, not achievement. Logistics are more convincing
        than adjectives: a key on a ring, mail being held, someone remembering
        to feed the dog. Avoid the word "manifest" and its relatives entirely.
        Avoid "abundance", "vibration", "alignment", "journey", "grateful for
        this beautiful". Never use an exclamation mark.

        OUTPUT

        The piece, then a final line on its own:

        NAMES: a comma-separated list of every person you named in the prose, or
        NAMES: none

        Count only people. Not places, not pets, not brands. This line is
        removed before anybody reads the piece, so it costs the reader nothing
        and it is not optional.
        PROMPT;
    }

    /**
     * Who, if anyone, may be named in this piece.
     *
     * This rule replaces a clause that said the names of other people in the
     * reader's life "must appear at least once" alongside "do not invent names
     * for people they did not name" — two instructions in direct contradiction,
     * and the contradiction went live exactly when somebody had named nobody.
     * Told that a name must appear and given none, a model does the only thing
     * left: it makes one up. A desire about a family ranch, with nobody
     * attached to it, came back with a brother called Zarak who does not exist.
     *
     * So the empty case is stated positively rather than as a prohibition. "Do
     * not invent names" is a rule about what not to write; "people appear by
     * their relationship" is a rule about what to write instead, and a model
     * follows the second far more reliably than the first. The piece that
     * invented Zarak also contained "one of my brothers' kids is arguing with a
     * dog", which is exactly the register being asked for here — it could
     * already do this, and only reached for a name because it was ordered to.
     *
     * @param  array<int, string>  $people  names the reader attached to this desire
     */
    private function namingRule(array $people): string
    {
        if ($people === []) {
            return <<<'N'
            NOBODY IS NAMED IN THIS PIECE. The reader has not told you the name
               of a single person, so you do not know one — and a name you chose
               yourself is a stranger standing in their life.

               People appear by their relationship instead, and this is the
               normal way to write it, not a workaround: "my brother", "his
               eldest", "one of the kids", "the neighbour with the dog". Write
               as many people as the piece needs. Give none of them a name.
            N;
        }

        $list = implode(', ', $people);

        return <<<N
            The only people who may be named are the ones the reader named:
               {$list}. Spell each exactly as written. Use them only where they
               fit; none of them has to appear.

               Anyone else appears by relationship — "my brother", "the
               neighbour". No other name may appear in the piece, including one
               that would seem to fit.
            N;
    }

    /**
     * The names the reader attached to this desire, and only those.
     *
     * The circle used to be sent whole, so a desire about a new job arrived
     * with a daughter attached and a rule saying to use her. `people_involved`
     * is the checkbox list of circle members on the desire form — it is the
     * reader saying who this one is about, and it was being passed to the model
     * as context while every other name was passed alongside it.
     *
     * A name that never reaches the prompt cannot reach the prose, which is
     * worth more than any instruction telling a model to ignore something.
     *
     * @return array<int, string>
     */
    private function peopleFor(User $user, ?Desire $desire): array
    {
        $named = collect($desire?->people_involved ?? [])
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values();

        return $named->all();
    }

    private function user(User $user, ?Desire $desire): string
    {
        $p = $user->world();
        $lines = [];

        $lines[] = 'THE READER';
        // Deliberately not "Call them" — that read as an instruction to use the
        // name, and the model duly wrote it into the prose in the third person.
        $lines[] = 'Their name (for your understanding only — never write it in the piece): '.$user->callMe();
        $this->add($lines, 'Lives in', $p->city);
        $this->add($lines, 'Where they are right now', $p->life_context);
        $this->add($lines, 'What matters most to them', $this->list($p->values));
        $this->add($lines, 'A place or object that grounds them', $p->anchor);

        /*
         * Only the people the reader attached to THIS desire.
         *
         * The whole circle used to go into every reading under "use these names
         * exactly", so a desire about a new job arrived carrying a daughter and
         * an instruction to write her in. Anyone not ticked on this desire is
         * not described here at all — a name that never reaches the prompt
         * cannot reach the prose, and that is worth more than telling a model
         * to ignore something already in front of it.
         */
        $named = $this->peopleFor($user, $desire);

        $circle = $user->circle->filter(
            fn ($person) => in_array(trim((string) $person->name), $named, true),
        );

        if ($circle->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'THE PEOPLE THEY NAMED FOR THIS ONE — spell these exactly; no other name may appear';

            foreach ($circle as $person) {
                // details() rather than ->note: a person can carry as many
                // facts as the user has bothered to record, and the specific
                // ones are exactly what stops a reading sounding generic.
                $bits = array_filter([$person->relationship, ...$person->details()]);
                $lines[] = '- '.$person->name.($bits ? ' ('.implode('; ', $bits).')' : '');
            }
        }

        if ($desire) {
            $lines[] = '';
            $lines[] = 'WHAT THEY ARE LIVING INSIDE';
            $this->add($lines, 'The desire', $desire->title);
            $this->add($lines, 'In their words', $desire->description);
            $this->add($lines, 'Why it matters', $desire->why_it_matters);
            $this->add($lines, 'How they want to feel', $this->list($desire->desired_feelings));
            $this->add($lines, 'Non-negotiable', $desire->non_negotiables);
            // Deliberately not repeated here: who may be named is rule 4's
            // subject, and listing them again as context reads as a checklist.

            $this->add($lines, 'Timeframe they named', $this->timeframe($desire->timeframe));

            if ($desire->open_to_better) {
                $lines[] = 'They are open to this arriving in a better form than they pictured — you may let one detail be different and better than asked for, without commenting on it.';
            }
        }

        $lines[] = '';
        $lines[] = 'Write the reading.';

        return implode("\n", $lines);
    }

    /* ── rules ───────────────────────────────────────────────────────────── */

    private function faithRule(?string $key): string
    {
        // Null is possible on a profile written before faith_language had a
        // model-level default; treat it as the secular default, which is also
        // the least presumptuous thing to send to a model.
        return match ($key ?? 'none') {
            'universe' => 'Spiritual register: the universe, energy, timing, alignment of circumstance. Never a personal deity.',
            'god'      => 'Spiritual register: God, prayer, blessing, thanksgiving. Reverent and plain, never preachy.',
            'spirit'   => 'Spiritual register: spirit, ancestors, guidance, being watched over. Warm rather than mystical.',
            'higher'   => 'Spiritual register: a higher power, left unnamed. Gesture at it once at most.',
            default    => 'No spiritual or religious vocabulary at all. Nothing is granted, guided or aligned — things simply are as they are.',
        };
    }

    private function styleRule(string $tone, string $style): string
    {
        $tones = [
            'grounded'  => 'Plain and unhurried. Short declarative sentences. No flourish.',
            'tender'    => 'Warm and close, the way someone speaks to a person they love. Still restrained.',
            'assured'   => 'Quietly certain. Nothing is being argued for; it is simply reported.',
            'reverent'  => 'Slow and a little formal, the register of something that matters.',
            'playful'   => 'Light, with dry humour in the small details. Never jokes about the desire itself.',
        ];

        $styles = [
            'cinematic'    => 'Write it as a scene. Establish where we are in the first two sentences, then move through the moment in real time.',
            'letter'       => 'Write it as if the reader were describing the day to one trusted person, unhurried and slightly conversational.',
            'meditative'   => 'Write it as a slow inventory of what is true right now, moving between the physical and the felt.',
            'documentary'  => 'Write it as an observer with no opinion, describing exactly what a camera would see and hear.',
        ];

        return ($tones[$tone] ?? $tones['grounded'])."\n".($styles[$style] ?? $styles['cinematic']);
    }

    private function timeframe(?string $key): ?string
    {
        return match ($key) {
            'this_month' => 'within a month',
            'this_year'  => 'within the year',
            'three_year' => 'within about three years',
            'someday'    => 'no fixed date — someday',
            default      => null,
        };
    }

    /* ── output shaping ──────────────────────────────────────────────────── */

    /**
     * Strip anything the model wrapped around the prose.
     *
     * Models occasionally open with "Here is your reading:" or fence the whole
     * thing in backticks despite being told not to. Cheap to remove here;
     * jarring if it reaches the reveal screen.
     */
    private function tidy(string $text): string
    {
        $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', trim($text));
        $text = preg_replace('/^(here (is|\'s)[^\n]*|your reading[^\n]*)\n+/i', '', $text);
        $text = preg_replace('/^#+\s*.*\n+/', '', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /** A title for the story list. Uses the desire, falling back to the prose. */
    private function titleFor(?Desire $desire, string $body): string
    {
        if ($desire && filled($desire->title)) {
            return Str::limit(trim($desire->title), 70, '');
        }

        $first = Str::before($body, '.');

        return Str::limit(trim($first), 60, '…');
    }

    private function length($profile, ?Desire $desire): string
    {
        $key = $desire?->story_length ?: $profile->default_length;

        return array_key_exists($key, config('escalate.lengths')) ? $key : 'medium';
    }

    private function add(array &$lines, string $label, $value): void
    {
        if (filled($value)) {
            $lines[] = "{$label}: {$value}";
        }
    }

    private function list($value): ?string
    {
        $items = collect(is_array($value) ? $value : [])->filter()->values();

        return $items->isEmpty() ? null : $items->implode(', ');
    }
}
