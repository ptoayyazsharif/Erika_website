<?php

namespace Tests\Feature;

use App\Models\Desire;
use App\Models\Rewind;
use App\Models\User;
use App\Services\AffirmationWriter;
use App\Services\RewindWriter;
use App\Services\StoryWriter;
use App\Support\Voice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * How Escalate speaks — asserted, so it does not drift back.
 *
 * The guide is docs/VOICE.md. Most of it is judgement and cannot be tested;
 * two parts of it can, and they are the two that went wrong on their own:
 *
 *   1. The named clichés. Checked as PHRASES, never as single words —
 *      "the universe" is legitimate for somebody who chose that belief
 *      language, and "Manifested" is a status a person picks about their own
 *      life. Banning the words would fail on copy that is correct.
 *
 *   2. Every belief language reaching every writer. This is the one with
 *      teeth: AffirmationWriter's rule was a copy of StoryWriter's with two
 *      arms missing, so people who chose "Spirit, ancestors, guidance" or
 *      "A higher power, unnamed" silently got secular cards. Nobody could see
 *      it, because nobody reads two prompts side by side. This test does.
 */
class VoiceTest extends TestCase
{
    use RefreshDatabase;

    /** The phrases the guide names, plus the ones that always travel with them. */
    private const CLICHES = [
        'calling it in',
        'energetic frequency',
        'highest timeline',
        'vibrational alignment',
        'the universe is conspiring',
        'divine timing',
        'raising your vibration',
        'law of attraction',
    ];

    /* ── the clichés appear nowhere a person can read them ───────────────── */

    public function test_no_cliche_appears_in_any_screen_a_person_sees(): void
    {
        $root = base_path('resources/views');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        $checked = 0;

        foreach ($files as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = mb_strtolower(file_get_contents($file->getPathname()));
            $checked++;

            foreach (self::CLICHES as $cliche) {
                $this->assertStringNotContainsString(
                    $cliche,
                    $contents,
                    "“{$cliche}” is in ".str_replace(base_path().'/', '', $file->getPathname()).'. See docs/VOICE.md.',
                );
            }
        }

        // A path typo that silently checked nothing would pass forever.
        $this->assertGreaterThan(40, $checked, 'The view sweep found almost no files, so it proved nothing.');
    }

    public function test_no_cliche_appears_in_the_copy_an_admin_can_edit(): void
    {
        $strings = array_merge(
            array_values(array_filter(config('escalate.copy'), 'is_string')),
            collect(config('escalate.emails'))->flatMap(fn ($e) => [$e['subject'], $e['body']])->all(),
            [config('escalate.push.title'), config('escalate.push.body')],
        );

        foreach ($strings as $string) {
            foreach (self::CLICHES as $cliche) {
                $this->assertStringNotContainsString($cliche, mb_strtolower($string), "“{$cliche}” is in the copy.");
            }
        }
    }

    /* ── the belief languages, through every writer ──────────────────────── */

    /**
     * The assertion this file exists for.
     *
     * Walks all five languages through all three writers and requires the
     * built prompt to differ. Remove an arm from Voice::faithRule() and two
     * languages collapse onto the secular default — which is exactly the bug
     * that shipped — and this goes red.
     */
    public function test_every_belief_language_reaches_every_writer(): void
    {
        $languages = array_keys(config('escalate.faith_languages'));
        $this->assertCount(5, $languages);

        foreach (['story', 'affirmation', 'rewind'] as $writer) {
            $seen = [];

            foreach ($languages as $language) {
                $prompt = $this->systemPromptFor($writer, $language);
                $rule = Voice::faithRule($language);

                $this->assertStringContainsString(
                    $this->flatten($rule),
                    $this->flatten($prompt),
                    "The {$writer} prompt does not carry the instruction for “{$language}”.",
                );

                $this->assertNotContains(
                    $rule,
                    $seen,
                    "Two belief languages produce the same instruction in the {$writer} prompt — "
                    ."“{$language}” is falling through to another arm.",
                );

                $seen[] = $rule;
            }
        }
    }

    /** Secular must say so, rather than saying nothing. */
    public function test_the_secular_default_forbids_spiritual_language_outright(): void
    {
        foreach ([null, '', 'none', 'something-nobody-configured'] as $value) {
            $this->assertStringContainsString('No spiritual or religious vocabulary', Voice::faithRule($value));
        }
    }

    /* ── the prompts themselves ──────────────────────────────────────────── */

    public function test_the_prompts_carry_the_cliche_ban_and_no_exclamation_marks(): void
    {
        foreach (['story', 'affirmation', 'rewind'] as $writer) {
            // Collapsed, because the ban is a wrapped block in a heredoc and a
            // phrase can sit across a line break. Asserting on the raw string
            // tests the line wrapping, not the rule.
            $prompt = $this->flatten($this->systemPromptFor($writer, 'none'));

            $this->assertStringContainsString('calling it in', $prompt,
                "The {$writer} prompt does not ban the clichés.");
            $this->assertStringContainsString('the universe is conspiring', $prompt,
                "The {$writer} prompt does not ban the clichés.");
            $this->assertStringContainsString('Never use an exclamation mark', $prompt,
                "The {$writer} prompt does not forbid exclamation marks.");
        }
    }

    /** A Rewind is written about things that really happened, so this one matters most. */
    public function test_the_rewind_prompt_refuses_to_decide_what_anything_meant(): void
    {
        $prompt = $this->flatten($this->systemPromptFor('rewind', 'none'));

        $this->assertStringContainsString('Do not decide what anything meant', $prompt);
        $this->assertStringContainsString('meant to be', $prompt);
        $this->assertStringContainsString('The meaning is theirs to make', $prompt);
    }

    /* ── helpers ─────────────────────────────────────────────────────────── */

    /** The built system prompt for one writer, for somebody with this belief language. */
    private function systemPromptFor(string $writer, ?string $language): string
    {
        $user = $this->makeUser("voice-{$writer}-".($language ?: 'null').'@escalate.test');
        $user->profile->forceFill(['faith_language' => $language])->save();
        $user = $user->fresh();

        return match ($writer) {
            'story' => $this->invoke(app(StoryWriter::class), 'system', [$user, $this->desireFor($user)]),
            'affirmation' => $this->invoke(app(AffirmationWriter::class), 'system', [$user, []]),
            'rewind' => $this->invoke(app(RewindWriter::class), 'system', [$user]),
        };
    }

    private function desireFor(User $user): Desire
    {
        $desire = $user->desires()->make();
        $desire->forceFill(['title' => 'A quieter week', 'description' => 'Mornings that are not a scramble.'])->save();

        return $desire->fresh();
    }

    /** One line, so an assertion is about the words and not the line wrapping. */
    private function flatten(string $text): string
    {
        return (string) preg_replace('/\s+/u', ' ', $text);
    }

    private function invoke(object $object, string $method, array $args): string
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);

        return (string) $ref->invokeArgs($object, $args);
    }
}
