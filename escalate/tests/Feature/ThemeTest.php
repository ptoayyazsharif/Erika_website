<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_chosen_theme_is_rendered_server_side(): void
    {
        $user = $this->makeUser('theme@escalate.test');
        $user->world()->update(['theme' => 'ember']);

        $html = $this->actingAs($user)->get(route('gratitude.index'))->assertOk()->getContent();

        // Server-rendered, not applied by JS. This is what removes the flash of
        // the wrong palette on first paint — the CSP forbids the inline head
        // script that would otherwise be needed.
        $this->assertStringContainsString('data-theme="ember"', $html);
        $this->assertStringContainsString('content="#16110D"', $html);
        $this->assertStringContainsString('content="dark"', $html);
    }

    public function test_a_theme_can_be_set_and_persists_to_the_account(): void
    {
        $user = $this->makeUser('theme@escalate.test');

        $this->actingAs($user)->postJson(route('world.theme'), ['theme' => 'tide'])
            ->assertOk()
            ->assertJson(['theme' => 'tide', 'scheme' => 'dark', 'counterpart' => 'parchment']);

        // On the account, not in localStorage, so it follows the user to a new
        // device.
        $this->assertSame('tide', $user->fresh()->profile->theme);
    }

    public function test_an_unknown_theme_is_refused(): void
    {
        $user = $this->makeUser('theme@escalate.test');

        $this->actingAs($user)->postJson(route('world.theme'), ['theme' => 'hotpink'])
            ->assertStatus(422);

        $this->actingAs($user)->postJson(route('world.theme'), ['theme' => '../../etc/passwd'])
            ->assertStatus(422);

        $this->assertSame('midnight', $user->fresh()->profile->theme);
    }

    public function test_every_configured_theme_has_a_matching_stylesheet_block(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        foreach (array_keys(config('escalate.themes')) as $key) {
            // A theme in config with no CSS block silently renders as the
            // default, which looks like the picker being broken.
            $this->assertStringContainsString(
                "[data-theme='{$key}']", $css,
                "Theme '{$key}' is configured but has no CSS block.",
            );
        }
    }

    public function test_every_theme_declares_a_counterpart_that_exists(): void
    {
        $themes = config('escalate.themes');

        foreach ($themes as $key => $theme) {
            $this->assertArrayHasKey($theme['counterpart'], $themes,
                "Theme '{$key}' points at a counterpart that does not exist.");

            // The quick switch is a light/dark toggle, so a pair that shares a
            // scheme would make the button appear to do nothing.
            $this->assertNotSame(
                $theme['scheme'], $themes[$theme['counterpart']]['scheme'],
                "Theme '{$key}' and its counterpart are both {$theme['scheme']}.",
            );
        }
    }

    public function test_a_guest_cannot_set_a_theme(): void
    {
        $this->postJson(route('world.theme'), ['theme' => 'tide'])->assertUnauthorized();
    }
}
