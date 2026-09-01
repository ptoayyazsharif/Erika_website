<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The colour a stranger sees, and Erika being able to change it.
 *
 * Every public page runs through layouts/auth.blade.php, which asks
 * active_theme(). A guest has no profile to read a preference from, so that
 * used to fall back to a hardcoded 'midnight' — the first thing anybody ever
 * saw was a colour nobody chose and nobody could change without a deploy.
 */
class PublicThemeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('themeadmin@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /* ── the two purple ones ─────────────────────────────────────────────── */

    public function test_the_purple_themes_exist_and_pair_with_each_other(): void
    {
        $themes = Theme::all();

        $this->assertArrayHasKey('amethyst', $themes);
        $this->assertArrayHasKey('wisteria', $themes);

        $this->assertSame('dark', $themes['amethyst']['scheme']);
        $this->assertSame('light', $themes['wisteria']['scheme']);

        // The toggle flips between them, as Midnight and Parchment do.
        $this->assertSame('wisteria', $themes['amethyst']['counterpart']);
        $this->assertSame('amethyst', $themes['wisteria']['counterpart']);
    }

    /**
     * Every theme declares every token, so none inherits half of Midnight's.
     *
     * A palette missing a token does not fail loudly — it silently falls
     * through to whatever was defined before it, which is how a "new theme"
     * ends up with the old one's accent on two screens out of ten.
     */
    public function test_every_theme_declares_the_same_tokens_as_midnight(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $tokensIn = function (string $selector) use ($css): array {
            preg_match('/\['.preg_quote($selector, '/').'\]\s*\{(.*?)\}/s', $css, $m);
            preg_match_all('/(--[a-z0-9-]+)\s*:/i', $m[1] ?? '', $found);

            return array_unique($found[1] ?? []);
        };

        $baseline = $tokensIn("data-theme='midnight'");

        $this->assertNotEmpty($baseline, 'Could not read the Midnight palette.');

        foreach (['amethyst', 'wisteria'] as $theme) {
            $missing = array_diff($baseline, $tokensIn("data-theme='{$theme}'"));

            $this->assertSame([], array_values($missing),
                "{$theme} is missing: ".implode(', ', $missing));
        }
    }

    /* ── what a stranger gets ────────────────────────────────────────────── */

    public function test_a_guest_sees_the_theme_the_admin_chose(): void
    {
        Config::set('escalate.public_theme', 'amethyst');

        foreach (['/', route('apply'), route('login'), route('privacy')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('data-theme="amethyst"', false);
        }
    }

    /** And the browser chrome follows it, not just the page. */
    public function test_the_chrome_colour_follows_the_public_theme(): void
    {
        Config::set('escalate.public_theme', 'wisteria');

        $this->get('/')
            ->assertOk()
            ->assertSee(Theme::all()['wisteria']['chrome'], false)
            ->assertSee('content="light"', false);
    }

    /** A person's own choice still beats the public setting. */
    public function test_somebody_signed_in_keeps_their_own_theme(): void
    {
        Config::set('escalate.public_theme', 'amethyst');

        $user = $this->makeUser('own@escalate.test');
        $user->profile->forceFill(['theme' => 'tide'])->save();

        $this->actingAs($user->fresh())
            ->get(route('today'))
            ->assertOk()
            ->assertSee('data-theme="tide"', false);
    }

    /** A nonsense setting falls back rather than rendering an unstyled page. */
    public function test_an_unknown_theme_falls_back(): void
    {
        Config::set('escalate.public_theme', 'chartreuse');

        $this->assertSame(Theme::FALLBACK, Theme::public());
        $this->get('/')->assertOk()->assertSee('data-theme="midnight"', false);
    }

    /* ── the picker ──────────────────────────────────────────────────────── */

    public function test_narrowing_the_list_narrows_the_picker(): void
    {
        Config::set('escalate.themes_offered', ['midnight', 'amethyst']);

        $this->assertSame(['midnight', 'amethyst'], array_keys(Theme::offered()));
    }

    /**
     * But never takes away the one somebody is already on.
     *
     * Hiding it would show them a picker with nothing selected, and quietly
     * move them off it the next time they saved anything.
     */
    public function test_a_theme_somebody_is_using_is_never_hidden_from_them(): void
    {
        Config::set('escalate.themes_offered', ['midnight']);

        $this->assertArrayHasKey('tide', Theme::offered('tide'));

        $user = $this->makeUser('keeps@escalate.test');
        $user->profile->forceFill(['theme' => 'tide'])->save();

        $this->actingAs($user->fresh())
            ->get(route('world.edit'))
            ->assertOk()
            ->assertSee('value="tide"', false);
    }

    /** An empty allowlist means everything, not nothing. */
    public function test_a_misconfigured_list_offers_everything_rather_than_nothing(): void
    {
        Config::set('escalate.themes_offered', ['nonexistent']);

        $this->assertSame(array_keys(Theme::all()), array_keys(Theme::offered()));
    }

    /* ── admin ───────────────────────────────────────────────────────────── */

    public function test_erika_can_change_the_public_theme_from_admin(): void
    {
        $this->admin();

        $this->put(route('admin.settings.update'), [
            'section' => 'look',
            'settings' => ['escalate__public_theme' => 'wisteria'],
        ])->assertRedirect();

        Settings::flush();
        Settings::apply();

        $this->assertSame('wisteria', Theme::public());
    }
}
