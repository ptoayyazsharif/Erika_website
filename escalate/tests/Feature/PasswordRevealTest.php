<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The "show password" control.
 *
 * Users asked for it on sign-in. What is asserted here is not that it looks
 * right — that was checked in a browser — but the two things that would break
 * silently: the button submitting the form it sits in, and the control being
 * left visible for a browser that cannot operate it.
 */
class PasswordRevealTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A bare <button> inside a form defaults to type="submit". Without an
     * explicit type, pressing "show password" would attempt a sign-in with a
     * half-typed password, and on the register form would fire validation over
     * a half-filled one. This is the assertion that matters most here.
     */
    public function test_the_toggle_never_submits_the_form_it_sits_in(): void
    {
        foreach ([route('login'), route('register')] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<button[^>]*data-reveal[^>]*>/', $html, $buttons);

            $this->assertNotEmpty($buttons[0], "No reveal toggle on {$url}.");

            foreach ($buttons[0] as $button) {
                $this->assertStringContainsString('type="button"', $button,
                    "A reveal toggle on {$url} would submit the form when pressed.");
            }
        }
    }

    /**
     * app.js opens by saying the app is fully usable with that file blocked, so
     * the button ships hidden and JS unhides it. A control that is visible but
     * inert is worse than no control.
     */
    public function test_the_toggle_is_hidden_until_javascript_says_otherwise(): void
    {
        preg_match_all(
            '/<button[^>]*data-reveal[^>]*>/',
            $this->get(route('login'))->getContent(),
            $buttons,
        );

        foreach ($buttons[0] as $button) {
            $this->assertMatchesRegularExpression('/\shidden[\s>]/', $button);
        }
    }

    /**
     * Every password field a person composes into gets one, and each points at
     * an input id that is actually on the page — an aria-controls naming
     * nothing is a screen-reader dead end that renders perfectly.
     */
    public function test_every_password_field_on_the_auth_screens_has_one(): void
    {
        $pages = [
            route('login') => 1,
            route('register') => 2,
            route('password.reset', ['token' => 'a-token']) => 2,
        ];

        foreach ($pages as $url => $expected) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/data-reveal="([^"]+)"/', $html, $reveals);

            $this->assertCount($expected, $reveals[1], "Wrong number of toggles on {$url}.");

            foreach ($reveals[1] as $id) {
                $this->assertMatchesRegularExpression(
                    '/<input[^>]*id="'.preg_quote($id, '/').'"[^>]*type="password"/',
                    $html,
                    "A toggle on {$url} controls \"{$id}\", which is not a password input on that page.",
                );
            }
        }
    }

    public function test_the_admin_door_has_one_too(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.login'))
            ->assertOk()
            ->assertSee('data-reveal="password"', false);
    }

    /**
     * Revealing is a client-side affordance. It must not have changed what the
     * form posts or how it is handled — the field is still `password`, and
     * signing in still works.
     */
    public function test_signing_in_still_works(): void
    {
        Config::set('escalate.beta.invite_only', false);

        $user = User::factory()->create([
            'email' => 'reveal@example.test',
            'password' => bcrypt('a-long-enough-password-1'),
            'email_verified_at' => now(),
        ]);

        $this->post(route('login.store'), [
            'email' => 'reveal@example.test',
            'password' => 'a-long-enough-password-1',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }
}
