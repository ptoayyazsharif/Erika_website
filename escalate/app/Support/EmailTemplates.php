<?php

namespace App\Support;

use Illuminate\Mail\Markdown;
use Illuminate\Support\HtmlString;

/**
 * Every email this app sends, in one place, with the words under Erika's
 * control rather than inside a Blade file.
 *
 * The same argument as App\Support\Copy: a launch week is exactly when the
 * wording changes, and every change being a deploy is the wrong shape. Config
 * holds today's exact sentences as the default, so an untouched install sends
 * precisely what it sent before; an admin edit is an override, not the source
 * of truth.
 *
 * ── What is editable, and what is not ────────────────────────────────────────
 *
 * Editable: the subject, and the prose body as Markdown.
 *
 * NOT editable, deliberately: invite codes, buttons, URLs, and the list of
 * answers on the admin email. Those stay in the Blade files. The worst an edit
 * can do is produce awkward wording — it cannot send a selection email with no
 * code in it, or a password reset with no link. Those are the failures that
 * make an email actively useless, and they are exactly what a free-text box
 * invites if you let it own the whole message.
 *
 * Tokens are substituted AFTER the Markdown is parsed, with the value escaped.
 * So a token is display-only, nothing structural depends on one, and deleting
 * every token from a body still produces a working email.
 */
class EmailTemplates
{
    /**
     * key => [label, when it is sent, the tokens its prose may use].
     *
     * @var array<string, array{label: string, blurb: string, tokens: array<int, string>}>
     */
    public const TEMPLATES = [
        'applied' => [
            'label'  => 'Thank you for applying',
            'blurb'  => 'To the applicant, the moment they submit the form.',
            'tokens' => ['name'],
        ],
        'selected' => [
            'label'  => 'You are in',
            'blurb'  => 'To the applicant when you select them. The code and the sign-up button are added automatically and cannot be removed here.',
            'tokens' => ['name', 'expires'],
        ],
        'revoked' => [
            'label'  => 'Invite released',
            'blurb'  => 'To somebody whose unused seat you took back. Only sent if that is switched on under Who gets in.',
            'tokens' => ['name'],
        ],
        'admin_application' => [
            'label'  => 'Somebody applied (to you)',
            'blurb'  => 'To every admin when an application arrives. The answers and the review button are added automatically.',
            'tokens' => ['name', 'email'],
        ],
        'password_reset' => [
            'label'  => 'Password reset',
            'blurb'  => 'When somebody asks to reset their password. The reset button is added automatically.',
            'tokens' => ['minutes'],
        ],
        'verify_email' => [
            'label'  => 'Confirm your email',
            'blurb'  => 'When somebody needs to confirm their address. The confirm button is added automatically.',
            'tokens' => [],
        ],
    ];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::TEMPLATES);
    }

    public static function subject(string $key, array $tokens = []): string
    {
        return self::substitute(
            (string) config("escalate.emails.{$key}.subject"),
            $tokens,
            escape: false,
        );
    }

    /**
     * The body as HTML, ready to echo.
     *
     * ── Why the angle brackets are escaped by hand ───────────────────────────
     *
     * `Markdown::parse()` does NOT escape HTML. It looks as though it does —
     * `html_input => 'escape'` appears in that file — but only inside the
     * `$encoded === true` branch, on a second converter used for interpolated
     * values. The default path builds a converter with `allow_unsafe_links`
     * alone, and CommonMark's own default for `html_input` is to allow it. So
     * a `<script>` an admin typed reached the rendered email intact, and a
     * test asserting otherwise is what caught it rather than the vendor code
     * reading as though it were safe.
     *
     * Escaping `<` and `>` before parsing is deliberate over passing
     * `html_input` to `Markdown::converter()`: that method is marked
     * `@internal`, so relying on it makes a framework upgrade able to reopen a
     * hole silently. Nothing in Markdown needs a literal angle bracket —
     * headings, bold, lists and links all still work — so this costs nothing
     * and depends on no parser configuration at all.
     *
     * Unsafe links are a separate matter and Laravel's default converter does
     * handle those: a `javascript:` href is dropped, verified in the same test.
     *
     * The views echo the result with {!! !!}, which CLAUDE.md otherwise
     * forbids. That is correct here and only here: what is echoed is parser
     * output over already-escaped input, not the admin's text.
     */
    public static function body(string $key, array $tokens = []): HtmlString
    {
        $source = str_replace(
            ['<', '>'],
            ['&lt;', '&gt;'],
            (string) config("escalate.emails.{$key}.body"),
        );

        return new HtmlString(
            self::substitute((string) Markdown::parse($source), $tokens, escape: true),
        );
    }

    /**
     * Tokens go in after parsing, so a value can never be read as Markdown and
     * can never introduce markup — a name with an asterisk in it stays a name.
     */
    private static function substitute(string $text, array $tokens, bool $escape): string
    {
        foreach ($tokens as $name => $value) {
            $value = (string) $value;

            $text = str_replace(
                ['{{ '.$name.' }}', '{{'.$name.'}}'],
                $escape ? e($value) : $value,
                $text,
            );
        }

        return $text;
    }
}
