<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The brand, asserted where it can be.
 *
 * Most of a logo is not testable — the previous concept failed by reading as an
 * F, and no assertion catches that; it was caught by rendering it and looking.
 * What is testable is that the mark actually reaches the pages a stranger sees,
 * that it is painted in currentColor so one file serves both grounds, and that
 * the reserved colour stays reserved. Those are the things that quietly stop
 * being true later.
 */
class BrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_pages_open_in_ivory(): void
    {
        // The default, with nothing in the settings table: Erika's warm neutral
        // is what ten people arriving from a DM get.
        $this->get('/')->assertOk()->assertSee('data-theme="ivory"', false);
        $this->get(route('apply'))->assertOk()->assertSee('data-theme="ivory"', false);
        $this->get(route('login'))->assertOk()->assertSee('data-theme="ivory"', false);
    }

    public function test_the_mark_is_on_the_pages_a_stranger_lands_on(): void
    {
        foreach (['/', route('apply'), route('login'), route('register')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('class="mark ', false)
                ->assertSee('>Escalate</span>', false);
        }
    }

    public function test_the_mark_is_in_the_topbar_for_a_signed_in_person(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('today'))
            ->assertOk()
            ->assertSee('class="mark ', false);
    }

    /**
     * Every palette says which colourway of the mark it needs.
     *
     * The mark used to be inline SVG painted in currentColor, so one file
     * served both grounds and there was nothing to get wrong. Erika's artwork
     * is a raster: a light theme has to name the dark file and a dark theme the
     * light one, and getting it backwards means an ivory mark on an ivory page
     * — invisible, and invisible in a way no page-renders-fine check catches.
     */
    public function test_every_theme_names_a_mark_and_names_the_right_one(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        preg_match_all(
            "/^((?:\\[data-theme='[a-z0-9-]+'\\]\\s*,\\s*)*\\[data-theme='[a-z0-9-]+'\\]\\s*)\\{(.*?)^\\}/sm",
            $css,
            $blocks,
            PREG_SET_ORDER,
        );

        $this->assertGreaterThanOrEqual(10, count($blocks), 'Could not read the palettes.');

        foreach ($blocks as [, $selector, $body]) {
            $name = trim($selector);

            preg_match('/color-scheme:\s*(light|dark);/', $body, $scheme);
            preg_match('/--mark-url:\s*url\(\x27([^\x27]+)\x27\)/', $body, $mark);

            $this->assertNotEmpty($mark[1] ?? null, "{$name} declares no --mark-url.");

            $wanted = ($scheme[1] ?? '') === 'light' ? 'mark-aubergine.png' : 'mark-ivory.png';

            $this->assertStringContainsString($wanted, $mark[1],
                "{$name} is {$scheme[1]} but points at ".basename($mark[1]).
                ' — that is the mark you cannot see on this ground.');
        }
    }

    public function test_both_colourways_are_actually_on_disk(): void
    {
        foreach (['mark-ivory.png', 'mark-aubergine.png'] as $file) {
            $path = public_path("brand/{$file}");

            $this->assertFileExists($path);

            // A truncated or placeholder PNG still "exists".
            $this->assertGreaterThan(20_000, filesize($path), "{$file} is too small to be the artwork.");
            $this->assertSame("\x89PNG", substr(file_get_contents($path, false, null, 0, 4), 0, 4));
        }
    }

    /**
     * Erika's rule: champagne means achievement — Founding Tester, completion,
     * a manifested state — and is not decoration.
     *
     * This started as a grep for the hex in the public templates and passed
     * while every eyebrow on every public page was gold, because .eyebrow is
     * painted from --brass and the brand themes had set --brass to the
     * champagne hex. A test that reports clean on the exact thing it exists to
     * catch is worse than no test, so it reads the palette instead: --brass
     * reaches most of the app, so it must not be the reserved colour.
     */
    public function test_champagne_is_not_the_colour_the_whole_app_is_painted_in(): void
    {
        $css = file_get_contents(public_path('css/app.css'));
        $champagne = config('escalate.brand.champagne');

        foreach (['ivory', 'aubergine'] as $theme) {
            preg_match("/\\[data-theme='{$theme}'\\]\\s*\\{(.*?)\\}/s", $css, $m);
            $block = $m[1] ?? '';

            $this->assertNotEmpty($block, "Could not read the {$theme} palette.");

            preg_match('/--brass:\s*([^;]+);/', $block, $brass);

            $this->assertNotSame(
                $champagne,
                trim($brass[1] ?? ''),
                "{$theme} paints --brass in champagne, which puts the reserved ".
                'colour on every eyebrow, tab indicator and progress bar in the app.',
            );

            $this->assertStringContainsString('--champagne:', $block,
                "{$theme} has nowhere to keep the reserved colour.");
        }
    }

    /**
     * And the reserved colour is actually reachable — a token nothing reads is
     * a token that gets deleted by the next person tidying up.
     */
    public function test_champagne_is_reachable_by_the_things_that_earned_it(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $this->assertMatchesRegularExpression(
            '/\.pill\.pill-founding\s*\{[^}]*var\(--champagne/s',
            $css,
            'Nothing on an achievement reads the reserved colour.',
        );
    }

    /**
     * Both brand utilities are declared beside the tokens that explain them,
     * which is several hundred lines above the component layer that defines
     * .btn and .pill. At equal specificity the later rule wins, so a single
     * class loses and the apply button renders flat accent — which is what it
     * did, and which looks deliberate rather than broken. The doubled selector
     * is the fix, and this is what stops somebody tidying it back.
     */
    public function test_the_brand_utilities_outrank_the_components_they_sit_above(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        foreach ([['.btn.btn-iridescent', '.btn'], ['.pill.pill-founding', '.pill']] as [$utility, $component]) {
            $this->assertStringContainsString("{$utility} {", $css,
                "{$utility} must be written doubled to outrank {$component}.");

            $this->assertLessThan(
                strpos($css, "
{$component} {"),
                strpos($css, "{$utility} {"),
                "{$utility} now sits below {$component}; the doubled selector is ".
                'no longer needed, but leaving this assertion stale is worse than removing it.',
            );
        }
    }

    public function test_the_installed_app_stands_on_the_brand_ground(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame(config('escalate.brand.aubergine'), $manifest['theme_color']);
        $this->assertSame(config('escalate.brand.aubergine'), $manifest['background_color']);
    }
}
