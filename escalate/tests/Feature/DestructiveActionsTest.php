<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every irreversible action must actually ask first.
 *
 * The confirmation in this app is one attribute — `data-confirm` on a
 * <form> — and app.js reads it off the form during a capture-phase submit
 * listener. Put it on the <button> instead and nothing reads it: the markup
 * still looks like a guarded delete, the code review still sees the sentence
 * the user was supposed to be shown, and the button deletes on one click.
 *
 * That is exactly what had happened to "Delete this Rewind" — the one screen
 * where a single press throws away six questions someone sat down and answered
 * about their own life.
 *
 * A PHP test cannot click a button, so this asserts the contract instead:
 * every destructive form carries the attribute, and it is on the element that
 * is actually read.
 */
class DestructiveActionsTest extends TestCase
{
    /** Route names whose forms must always be confirmed before they submit. */
    private const MUST_CONFIRM = [
        'rewinds.destroy',
        'stories.destroy',
        'desires.destroy',
        'gratitude.destroy',
        'account.destroy',
    ];

    public function test_every_destructive_form_carries_its_confirmation(): void
    {
        foreach ($this->views() as $path => $markup) {
            foreach (self::MUST_CONFIRM as $route) {
                foreach ($this->formsPosting($markup, $route) as $form) {
                    $this->assertStringContainsString(
                        'data-confirm',
                        $this->openingTag($form),
                        basename($path).' submits to '.$route.' without data-confirm on the <form>. '
                            .'app.js reads the attribute from the form, so anywhere else it is decoration '
                            .'and the action fires on one click.',
                    );
                }
            }
        }
    }

    /**
     * And the script keeps honouring a misplaced one.
     *
     * Belt and braces: if the attribute drifts back onto a button, the guard
     * should degrade to a working prompt rather than disappear.
     */
    public function test_the_script_also_honours_a_confirmation_on_the_pressed_button(): void
    {
        $this->assertStringContainsString(
            'e.submitter?.dataset.confirm',
            file_get_contents(public_path('js/app.js')),
            'initConfirm() no longer falls back to the submitter, so a data-confirm '
                .'on a <button> is silently ignored again.',
        );
    }

    /* ── helpers ─────────────────────────────────────────────────────────── */

    /** @return array<string, string> */
    private function views(): array
    {
        $files = array_merge(
            glob(resource_path('views/*.blade.php')),
            glob(resource_path('views/**/*.blade.php')),
        );

        return array_combine($files, array_map('file_get_contents', $files));
    }

    /** Every <form>…</form> in $markup whose action names $route. */
    private function formsPosting(string $markup, string $route): array
    {
        preg_match_all('/<form\b.*?<\/form>/s', $markup, $matches);

        return array_values(array_filter(
            $matches[0],
            fn ($form) => str_contains($form, "route('{$route}'"),
        ));
    }

    private function openingTag(string $form): string
    {
        preg_match('/<form\b[^>]*>/s', $form, $tag);

        return $tag[0] ?? '';
    }
}
