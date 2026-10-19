<?php

namespace App\Support;

/**
 * How Escalate speaks, in the one place all three writers read from.
 *
 * The full guide is in docs/VOICE.md. This holds the fragments that reach a
 * model, and it exists because they were duplicated across StoryWriter,
 * AffirmationWriter and RewindWriter and had already drifted apart in a way
 * nobody could see from outside:
 *
 *   - AffirmationWriter::faithRule() was a copy of StoryWriter's with two of
 *     the five belief languages missing. Somebody who chose "Spirit, ancestors,
 *     guidance" or "A higher power, unnamed" got the SECULAR instruction on
 *     their cards, silently, while their readings used the register they asked
 *     for. Its docblock claimed the rule was shared. It was not.
 *   - RewindWriter took no belief language at all.
 *
 * That is the argument for this class: three copies of a rule are three chances
 * to be wrong, and the wrongness is invisible because nobody reads all three
 * prompts side by side. There is now one copy and a test that walks every
 * language through every writer.
 */
class Voice
{
    /**
     * The register a person asked to be written in.
     *
     * ── Why the cliché ban lifts here, deliberately ──────────────────────────
     *
     * The guide says to avoid manifestation vocabulary "unless the user's
     * explicit language/belief preference supports that vocabulary". Somebody
     * who chose "The universe, energy, alignment" in My World asked for exactly
     * that register. Withholding it is not neutrality — it is overriding a
     * preference they set on purpose.
     *
     * So this is the one place the ban lifts, and it lifts only as far as the
     * person's own choice. The prompts previously banned "alignment" outright
     * and then instructed "alignment of circumstance" a few lines later; the
     * model was being given a contradiction and left to resolve it.
     *
     * Secular is the default and the fallback for an unknown value, because the
     * failure that matters is putting spiritual language in front of somebody
     * who did not ask for it.
     */
    public static function faithRule(?string $key): string
    {
        return match ($key ?: 'none') {
            'universe' => 'They have asked for the language of the universe, energy and timing — '
                .'use it plainly and sparingly, and never as a personal deity. This is the one '
                .'register where that vocabulary is wanted, because they chose it.',

            'god' => 'They have asked for the language of God, prayer, blessing and thanks. '
                .'Plain and unforced. Never preachy, and never on their behalf — you are not '
                .'telling them what to believe about what happened.',

            'spirit' => 'They have asked for the language of spirit, ancestors and being '
                .'watched over. Warm and matter-of-fact rather than mystical.',

            'higher' => 'They have asked for the language of a higher power, left unnamed. '
                .'Gesture at it once at most, and never explain it.',

            default => 'No spiritual or religious vocabulary at all. Nothing is granted, guided '
                .'or aligned. Write about what they did, what is there, and what changed.',
        };
    }

    /**
     * The vocabulary floor, applied whatever the register.
     *
     * Every phrase here is one the guide names. They are banned as *phrases*
     * rather than as words, so that a person's own chosen register is still
     * reachable through faithRule() — "the universe" is allowed to somebody who
     * asked for it; "the universe is conspiring" is allowed to nobody, because
     * it is a cliché rather than a belief.
     */
    public static function clicheRule(): string
    {
        return <<<'V'
        Never use these, in any register, however the reader writes: "calling it
        in", "energetic frequency", "highest timeline", "vibrational alignment",
        "the universe is conspiring", "divine timing", "abundance", "raising
        your vibration", "the law of attraction". They are the vocabulary of a
        genre, not of a person's life, and they are the fastest way to make this
        sound like everybody else. Never use an exclamation mark.
        V;
    }

    /**
     * Vivid is not the same as poetic, and the difference is the whole product.
     *
     * The guide is explicit: specific details are more valuable than elaborate
     * metaphors, and the reaction being aimed at is "I can actually see myself
     * living this" — not "that was beautifully written".
     */
    public static function vividRule(): string
    {
        return <<<'V'
        Vivid, not poetic. A concrete detail beats a metaphor every time: the
        number on the screen, the name of the street, what somebody actually
        said, which door sticks. Reach for the ordinary and the specific, not
        the lyrical. If a sentence sounds like writing, cut it back until it
        sounds like a life.

        The reaction you are aiming for is "I can actually see myself living
        this", never "that was beautifully written".
        V;
    }

    /**
     * Nobody gets told what their life meant.
     *
     * The guide's sharpest rule, and the one with a real edge: do not tell
     * somebody an event was destiny, a sign, divine intervention or meant to
     * be unless they have said so themselves. It matters most in a Rewind,
     * which is written about things that actually happened — including things
     * that went wrong — and where a reassuring interpretation is the most
     * tempting thing to add and the least welcome thing to receive.
     */
    public static function meaningRule(): string
    {
        return <<<'V'
        Do not decide what anything meant. Nothing was destiny, a sign, a
        lesson, divine intervention or meant to be, and nothing happened "for a
        reason" — unless they said so themselves, in their own words, in which
        case you may use their reading of it and never improve on it. Describe
        what happened and what is there. The meaning is theirs to make.
        V;
    }
}
