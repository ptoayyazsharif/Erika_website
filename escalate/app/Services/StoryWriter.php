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

        $story->body = $this->tidy($text);
        $story->title = $this->titleFor($desire, $story->body);
        $story->word_count = str_word_count($story->body);
        $story->model = config('escalate.story.model');
        $story->state = 'ready';
        $story->save();
    }

    /* ── prompts ─────────────────────────────────────────────────────────── */

    private function system(User $user, ?Desire $desire): string
    {
        $profile = $user->world();
        [$low, $high] = config("escalate.lengths.{$this->length($profile, $desire)}.words");

        $perspective = match ($desire?->perspective ?: $profile->perspective) {
            'second' => 'Second person — "you are". Address the reader directly.',
            default  => 'First person — "I am". The reader is speaking about their own life.',
        };

        $faith = $this->faithRule($profile->faith_language);
        $style = $this->styleRule($desire?->tone ?: $profile->tone, $profile->story_style);

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
        4. Use the reader's own specifics. Every name, place, number and object
           they gave you must appear at least once, spelled exactly as they
           wrote it. Do not invent names for people they did not name.
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

        Output only the piece.
        PROMPT;
    }

    private function user(User $user, ?Desire $desire): string
    {
        $p = $user->world();
        $lines = [];

        $lines[] = 'THE READER';
        $lines[] = 'Call them: '.$user->callMe();
        $this->add($lines, 'Lives in', $p->city);
        $this->add($lines, 'Where they are right now', $p->life_context);
        $this->add($lines, 'What matters most to them', $this->list($p->values));
        $this->add($lines, 'A place or object that grounds them', $p->anchor);

        $circle = $user->circle;

        if ($circle->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'THEIR CIRCLE — use these names exactly, only where they fit naturally';

            foreach ($circle as $person) {
                $bits = array_filter([$person->relationship, $person->note]);
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
            $this->add($lines, 'People involved', $this->list($desire->people_involved));
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

    private function faithRule(string $key): string
    {
        return match ($key) {
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
