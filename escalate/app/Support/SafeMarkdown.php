<?php

namespace App\Support;

use Illuminate\Mail\Markdown;
use Illuminate\Support\HtmlString;

/**
 * Markdown an administrator typed, rendered so it cannot bite.
 *
 * Extracted from EmailTemplates in Stage C because announcements need exactly
 * the same treatment, and a second copy of this reasoning is a second place for
 * it to be got wrong.
 *
 * `Markdown::parse()` does NOT escape HTML. It reads as though it does —
 * `html_input => 'escape'` appears in that file — but only inside the
 * `$encoded === true` branch, on a converter used for interpolated values. The
 * default path builds one with `allow_unsafe_links` alone, and CommonMark's own
 * default allows raw HTML. A `<script>` an admin typed reached a rendered email
 * intact; a test caught it, the vendor code reading as safe did not.
 *
 * So angle brackets are escaped here, before parsing. Deliberately that way
 * round rather than passing `html_input` to `Markdown::converter()`, which is
 * marked `@internal` — depending on it lets a framework upgrade reopen the hole
 * in silence. Nothing in Markdown needs a literal angle bracket, so headings,
 * bold, lists and links all still work and this costs nothing.
 *
 * Unsafe links are separate and Laravel's default converter does handle them:
 * a `javascript:` href is dropped.
 *
 * Callers echo the result with {!! !!}, which CLAUDE.md otherwise forbids. That
 * is correct here and only here: what is echoed is parser output over
 * already-escaped input, never the admin's text.
 */
class SafeMarkdown
{
    public static function render(string $text): HtmlString
    {
        $escaped = str_replace(['<', '>'], ['&lt;', '&gt;'], $text);

        return new HtmlString((string) Markdown::parse($escaped));
    }
}
