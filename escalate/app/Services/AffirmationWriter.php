<?php

namespace App\Services;

use App\Models\Affirmation;
use App\Models\AffirmationSet;
use App\Models\Desire;
use App\Models\User;
use App\Services\Ai\Anthropic;
use RuntimeException;

/**
 * A day's affirmation cards, drawn from what someone is actually working toward.
 *
 * The difference between this and every affirmation app is the context block:
 * these are not "I am worthy" pulled from a list of four hundred, they name the
 * desire, the city, the person, the number. That is the same reason the
 * readings work, so this reuses the same context the reading prompt builds — a
 * card that could have been written for anyone is a card nobody keeps.
 *
 * Each card has two sides. The front is the affirmation, present tense and
 * first person; the back is one line saying what in their own life it is
 * standing on. The back exists because an affirmation you do not believe is
 * just a sentence, and the belief comes from the evidence, not the volume.
 */
class AffirmationWriter
{
    /** How many cards a set holds. Enough to sit with, few enough to finish. */
    public const CARDS = 5;

    public function __construct(private Anthropic $anthropic) {}

    public function write(AffirmationSet $set): void
    {
        $user = $set->user;
        $desires = $this->desiresFor($user);

        $text = $this->anthropic->write(
            $this->system($user),
            $this->context($user, $desires),
            $user,
            'affirmation',
        );

        $cards = $this->parse($text);

        if ($cards === []) {
            throw new RuntimeException('The writing service returned no usable cards.');
        }

        /*
         * The desire id on a card came out of a language model, and a model
         * that has been asked to echo an id will sometimes return a different
         * one — a hallucinated number, or one it saw in an earlier turn.
         * `affirmations.desire_id` has a foreign key but no ownership
         * constraint, so an id belonging to somebody else would attach cleanly
         * and quietly link this person's card to another person's desire.
         *
         * Only the ids we ourselves put in the prompt are allowed back out.
         */
        $ownIds = $desires->pluck('id')->all();

        foreach (array_values($cards) as $position => $card) {
            $affirmation = new Affirmation;

            $desireId = in_array($card['desire_id'], $ownIds, true) ? $card['desire_id'] : null;

            // forceFill: user_id and desire_id are server-derived, and nothing
            // a client posts may set either.
            $affirmation->forceFill([
                'affirmation_set_id' => $set->id,
                'user_id'            => $user->id,
                'desire_id'          => $desireId,
                'body'               => $card['body'],
                'back'               => $card['back'],
                'position'           => $position,
            ])->save();
        }

        $set->forceFill([
            'state' => 'ready',
            'model' => config('escalate.story.model'),
        ])->save();

        $user->world()->forceFill(['affirmations_generated_at' => now()])->save();
    }

    /**
     * The desires a card may be about.
     *
     * Live ones only, newest first, capped. Somebody with forty desires does
     * not want five cards drawn from the two they wrote in January, and a
     * prompt carrying all forty is mostly noise.
     */
    private function desiresFor(User $user)
    {
        return $user->desires()
            ->whereIn('status', ['desired', 'unfolding'])
            ->latest()
            ->take(6)
            ->get();
    }

    /* ── prompts ─────────────────────────────────────────────────────────── */

    private function system(User $user): string
    {
        $profile = $user->world();
        $faith = $this->faithRule($profile->faith_language);
        $cards = self::CARDS;

        return <<<PROMPT
        You write affirmation cards: single sentences a person reads to
        themselves, in their own voice, about a life they are already living.

        NON-NEGOTIABLE RULES

        1. First person and present tense, always. "I am", "I have", "I do".
           Never "I will", never "I am becoming", never "I am learning to".
           The thing is already true.
        2. Exactly {$cards} cards.
        3. Each card is two lines, in this exact shape, and nothing else:

           FRONT: the affirmation. One sentence, under 18 words.
           BACK: one sentence naming what in their own life this stands on.

        4. Use their specifics. The desire, the city, the person, the number
           they gave you — a card that could have been written for a stranger
           is a failed card. But never write their own name in the affirmation.
        5. The BACK line is evidence, not encouragement. It points at something
           real they told you: a thing they already do, already have, already
           decided. Never "you can do this". Never a compliment.
        6. Plain language. No exclamation marks, no "abundance", no "manifest",
           no "vibration", no "journey", no capitalised Universe unless their
           own words below use that register.
        7. {$faith}

        OUTPUT

        {$cards} cards, separated by a blank line. Each card is exactly two
        lines beginning "FRONT:" and "BACK:". No numbering, no headings, no
        preamble, no closing remark. Begin with the first FRONT line.
        PROMPT;
    }

    private function context(User $user, $desires): string
    {
        $p = $user->world();
        $lines = [];

        $lines[] = 'THE READER';
        $lines[] = 'Their name (for your understanding only — never write it on a card): '.$user->callMe();
        $this->add($lines, 'Lives in', $p->city);
        $this->add($lines, 'Where they are right now', $p->life_context);
        $this->add($lines, 'What matters most to them', $this->list($p->values));
        $this->add($lines, 'A place or object that grounds them', $p->anchor);

        if ($user->circle->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'THEIR CIRCLE — real people; use these names exactly, only where they fit';

            foreach ($user->circle as $person) {
                $bits = array_filter([$person->relationship, ...$person->details()]);
                $lines[] = '- '.$person->name.($bits ? ' ('.implode('; ', $bits).')' : '');
            }
        }

        if ($desires->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'WHAT THEY ARE WORKING TOWARD — spread the cards across these';

            foreach ($desires as $desire) {
                $lines[] = '';
                $lines[] = '### DESIRE '.$desire->id.': '.$desire->title;
                $this->add($lines, 'In their words', $desire->description);
                $this->add($lines, 'Why it matters', $desire->why_it_matters);
                $this->add($lines, 'How they want to feel', $this->list($desire->desired_feelings));
            }

            $lines[] = '';
            $lines[] = 'Where a card is about one of these, end the BACK line with (DESIRE <id>).';
        } else {
            $lines[] = '';
            $lines[] = 'They have not written a desire yet. Draw the cards from who they are above.';
        }

        $lines[] = '';
        $lines[] = 'Write the cards.';

        return implode("\n", $lines);
    }

    /** Shared with StoryWriter's rule, so both speak in the same register. */
    private function faithRule(?string $key): string
    {
        return match ($key ?? 'none') {
            'universe' => 'Spiritual register: the universe, energy, timing, alignment of circumstance. Never a personal deity.',
            'god'      => 'Spiritual register: God, prayer, blessing, thanksgiving. Reverent and plain, never preachy.',
            default    => 'No spiritual or religious language at all. Keep it ordinary and concrete.',
        };
    }

    /* ── parsing ─────────────────────────────────────────────────────────── */

    /**
     * Turn FRONT/BACK lines into cards.
     *
     * Written to survive a model that decides to number the cards, bold the
     * labels, or add a sentence of introduction — because it will. Anything
     * that does not parse is dropped rather than stored, and a set with no
     * cards at all is a failure the caller reports.
     *
     * @return array<int, array{body: string, back: ?string, desire_id: ?int}>
     */
    private function parse(string $text): array
    {
        $cards = [];
        $current = null;

        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(preg_replace('/^[\s>*\-\d.)#]+/', '', $line));

            if (preg_match('/^\**FRONT\**\s*:\s*(.+)$/i', $line, $m)) {
                if ($current && filled($current['body'])) {
                    $cards[] = $current;
                }

                $current = ['body' => $this->clean($m[1]), 'back' => null, 'desire_id' => null];

                continue;
            }

            if (preg_match('/^\**BACK\**\s*:\s*(.+)$/i', $line, $m) && $current) {
                $back = $this->clean($m[1]);

                // The desire tag is a routing instruction, not part of the
                // sentence. Take it off before anybody reads the card.
                if (preg_match('/\(\s*DESIRE\s+(\d+)\s*\)\s*$/i', $back, $tag)) {
                    $current['desire_id'] = (int) $tag[1];
                    $back = trim(preg_replace('/\(\s*DESIRE\s+\d+\s*\)\s*$/i', '', $back));
                }

                $current['back'] = $back ?: null;
            }
        }

        if ($current && filled($current['body'])) {
            $cards[] = $current;
        }

        return array_slice($cards, 0, self::CARDS);
    }

    private function clean(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($s)), " \t\n\r\0\x0B\"'*");
    }

    private function add(array &$lines, string $label, $value): void
    {
        if (filled($value)) {
            $lines[] = $label.': '.$value;
        }
    }

    private function list($value): ?string
    {
        return filled($value) ? implode(', ', (array) $value) : null;
    }
}
