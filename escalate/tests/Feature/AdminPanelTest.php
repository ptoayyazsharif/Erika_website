<?php

namespace Tests\Feature;

use App\Models\Invite;
use App\Models\Setting;
use App\Models\User;
use App\Support\Plan;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The admin area.
 *
 * Most of these are about what an administrator must NOT be able to do. The
 * area can set API keys and grant paid access, which makes an admin session the
 * one most worth stealing — so the interesting assertions are the boundaries,
 * not the happy paths.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /** An admin who has been through the second door. */
    private function admin(string $email = 'admin@escalate.test'): User
    {
        $user = $this->makeUser($email, 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /* ── the door ────────────────────────────────────────────────────────── */

    /** Every admin route is invisible to an ordinary account. */
    public function test_an_ordinary_user_gets_a_404_from_every_admin_route(): void
    {
        $user = $this->makeUser('ordinary@escalate.test');
        $other = $this->makeUser('other@escalate.test');
        $invite = Invite::mint();

        $routes = [
            ['get', route('admin.dashboard')],
            ['get', route('admin.settings')],
            ['put', route('admin.settings.update')],
            ['post', route('admin.settings.reset')],
            ['get', route('admin.users')],
            ['get', route('admin.users.show', $other)],
            ['patch', route('admin.users.plan', $other)],
            ['post', route('admin.users.suspend', $other)],
            ['get', route('admin.invites')],
            ['post', route('admin.invites.store')],
            ['delete', route('admin.invites.destroy', $invite)],
        ];

        foreach ($routes as [$verb, $url]) {
            $this->actingAs($user)->{$verb}($url)->assertNotFound();
        }
    }

    /**
     * The role alone is not enough — the second password door still stands.
     *
     * Turned away, but to the door rather than into a wall. 404'ing here made
     * /admin unreachable for a real admin who had never been told the login
     * URL existed, which is the state every admin starts in.
     */
    public function test_an_admin_without_the_second_door_is_sent_to_it(): void
    {
        $user = $this->makeUser('halfway@escalate.test');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    /** And it remembers where they were going. */
    public function test_the_door_returns_them_to_the_page_they_asked_for(): void
    {
        $user = $this->makeUser('deeplink@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->get(route('admin.settings.section', 'limits'))
            ->assertRedirect(route('admin.login'));

        // The real password, and the flag asserted alongside the destination:
        // a rejected password redirects back to the page it came from, which
        // here is admin.settings too, so the destination alone proves nothing.
        $this->post(route('admin.login.store'), ['password' => 'a-long-enough-password-1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.settings.section', 'limits'));

        $this->assertTrue(session('admin.verified'));
    }

    /**
     * A non-admin still cannot tell the admin area apart from a typo.
     *
     * This is the property the redirect above must not cost: the difference
     * between 404 and a redirect is only visible to someone who already holds
     * an admin account, and that is precisely what the 404 is hiding.
     */
    public function test_an_ordinary_user_still_gets_a_flat_404(): void
    {
        $this->actingAs($this->makeUser('curious@escalate.test'))
            ->get(route('admin.dashboard'))
            ->assertNotFound();

        $this->actingAs($this->makeUser('curious2@escalate.test'))
            ->get(route('admin.login'))
            ->assertNotFound();
    }

    public function test_an_admin_who_came_through_the_door_gets_in(): void
    {
        $this->admin();

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Overview');
        $this->get(route('admin.settings'))->assertOk();
        $this->get(route('admin.settings.section', 'limits'))->assertOk();
        $this->get(route('admin.users'))->assertOk();
        $this->get(route('admin.invites'))->assertOk();
    }

    /* ── settings ────────────────────────────────────────────────────────── */

    public function test_a_setting_is_saved_and_takes_effect_immediately(): void
    {
        $this->admin();

        $this->put(route('admin.settings.update'), [
            'settings' => ['escalate__ceiling__stories_per_day' => '42'],
        ])->assertRedirect();

        Settings::flush();
        Settings::apply();

        $this->assertSame(42, config('escalate.ceiling.stories_per_day'));
    }

    /**
     * The allowlist is the security boundary.
     *
     * Without it, "save these settings" is an arbitrary config write and
     * app.key is one crafted field name away.
     */
    public function test_a_key_outside_the_allowlist_cannot_be_written(): void
    {
        $this->admin();

        $before = config('app.key');

        $this->put(route('admin.settings.update'), [
            'settings' => [
                'app__key'                              => 'base64:hijacked',
                'database__connections__sqlite__database' => '/etc/passwd',
                'escalate__beta__invite_only'           => '1',
            ],
        ])->assertRedirect();

        $this->assertSame($before, config('app.key'));
        $this->assertSame(0, Setting::where('key', 'app.key')->count());
        $this->assertSame(0, Setting::where('key', 'database.connections.sqlite.database')->count());
    }

    /** Settings::put refuses directly too, not only through the controller. */
    public function test_put_refuses_a_key_outside_the_allowlist(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        Settings::put('app.key', 'base64:nope');
    }

    /**
     * A secret is never rendered back, not even to the admin who set it.
     *
     * The page is one of the highest-value things in the app to steal a session
     * for; printing live API keys would turn that into stolen vendor accounts.
     */
    public function test_the_settings_page_never_prints_an_api_key(): void
    {
        Config::set('escalate.anthropic.key', 'sk-ant-supersecretvalue-1234');
        Config::set('escalate.elevenlabs.key', 'el-secretvalue-9876');

        $this->admin();

        // Every section, not just the one the keys live on: a key leaking onto
        // a page it has no business being on is exactly the leak worth finding.
        foreach (array_keys(\App\Support\Settings::sections()) as $section) {
            $this->get(route('admin.settings.section', $section))
                ->assertOk()
                ->assertDontSee('sk-ant-supersecretvalue-1234')
                ->assertDontSee('el-secretvalue-9876');
        }

        // And the tail does show, so one key can be told from another.
        $this->get(route('admin.settings.section', 'ai'))
            ->assertOk()
            ->assertSee('••••1234')
            ->assertSee('••••9876');
    }

    /**
     * Saving the page with the secret box untouched must not wipe the key.
     *
     * The box is always empty, because the value is never rendered — so
     * treating empty as "delete" would destroy the API key of anyone who came
     * here to change a quota.
     */
    public function test_saving_with_a_blank_secret_leaves_the_existing_key_alone(): void
    {
        $this->admin();

        Settings::put('escalate.anthropic.key', 'sk-ant-original');

        $this->put(route('admin.settings.update'), [
            'settings' => [
                'escalate__anthropic__key'            => '',
                'escalate__ceiling__stories_per_day'  => '10',
            ],
        ])->assertRedirect();

        Settings::flush();

        $this->assertSame('sk-ant-original', Setting::where('key', 'escalate.anthropic.key')->first()->value);
    }

    public function test_a_secret_is_encrypted_at_rest(): void
    {
        Settings::put('escalate.anthropic.key', 'sk-ant-plaintext-check');

        $raw = Setting::where('key', 'escalate.anthropic.key')->first()->getRawOriginal('value');

        $this->assertStringNotContainsString('sk-ant-plaintext-check', $raw);
    }

    public function test_an_override_can_be_reset_back_to_the_deployed_value(): void
    {
        $this->admin();

        Settings::put('escalate.ceiling.stories_per_day', '7');
        $this->assertTrue(Settings::isOverridden('escalate.ceiling.stories_per_day'));

        $this->post(route('admin.settings.reset'), ['key' => 'escalate.ceiling.stories_per_day'])
            ->assertRedirect();

        $this->assertFalse(Settings::isOverridden('escalate.ceiling.stories_per_day'));
    }

    public function test_a_non_numeric_limit_is_refused(): void
    {
        $this->admin();

        $this->put(route('admin.settings.update'), [
            'settings' => ['escalate__ceiling__stories_per_day' => 'lots'],
        ])->assertSessionHasErrors('settings');
    }

    /**
     * A row for a key later removed from the allowlist must stop applying,
     * rather than staying quietly in force.
     */
    public function test_a_stored_row_outside_the_allowlist_is_ignored_on_apply(): void
    {
        $setting = new Setting;
        $setting->forceFill(['key' => 'app.name', 'value' => 'Hijacked'])->save();

        Settings::flush();
        $before = config('app.name');
        Settings::apply();

        $this->assertSame($before, config('app.name'));
    }

    /* ── people ──────────────────────────────────────────────────────────── */

    public function test_an_admin_can_comp_someone_onto_a_plan_without_touching_stripe(): void
    {
        Config::set('escalate.billing.enabled', true);
        Config::set('escalate.plans.monthly.price', 'price_monthly_test');

        $person = $this->makeUser('comped@escalate.test');
        $this->admin();

        $this->patch(route('admin.users.plan', $person), ['plan' => 'monthly'])->assertRedirect();

        $person = $person->fresh();

        $this->assertSame('monthly', Plan::for($person));
        $this->assertSame(5, \App\Support\Quota::limit($person, 'story'));
        // No subscription was fabricated to do it.
        $this->assertSame(0, $person->subscriptions()->count());
        $this->assertNull($person->stripe_id);
    }

    public function test_removing_the_override_returns_them_to_their_subscription(): void
    {
        Config::set('escalate.billing.enabled', true);

        $person = $this->makeUser('uncomped@escalate.test');
        $person->forceFill(['plan_override' => 'monthly'])->save();

        $this->admin();

        $this->patch(route('admin.users.plan', $person), ['plan' => ''])->assertRedirect();

        $this->assertNull($person->fresh()->plan_override);
        $this->assertSame('free', Plan::for($person->fresh()));
    }

    public function test_a_plan_that_does_not_exist_is_refused(): void
    {
        $person = $this->makeUser('nope@escalate.test');
        $this->admin();

        $this->patch(route('admin.users.plan', $person), ['plan' => 'enterprise'])
            ->assertSessionHasErrors('plan');

        $this->assertNull($person->fresh()->plan_override);
    }

    public function test_suspending_takes_effect_on_the_next_request(): void
    {
        $person = $this->makeUser('tosuspend@escalate.test');
        $admin = $this->admin();

        $this->post(route('admin.users.suspend', $person))->assertRedirect();
        $this->assertNotNull($person->fresh()->suspended_at);

        // And the suspended person is turned out mid-session.
        $this->actingAs($person->fresh())->get(route('today'))->assertRedirect(route('login'));
    }

    /** Locking yourself out is not undoable from here, so it is not offered. */
    public function test_an_admin_cannot_suspend_their_own_account(): void
    {
        $admin = $this->admin();

        $this->post(route('admin.users.suspend', $admin))->assertSessionHasErrors('user');

        $this->assertNull($admin->fresh()->suspended_at);
    }

    /** The area shows counts, never anything anybody wrote. */
    public function test_the_people_screens_never_render_journal_content(): void
    {
        $person = $this->makeUser('private@escalate.test');
        $person->desires()->create(['title' => 'A house on the water at Elm Street']);
        $person->gratitudeEntries()->create([
            'body' => 'The coffee was good and Marta called back.',
            'for_date' => today(),
        ]);

        $this->admin();

        foreach ([route('admin.users'), route('admin.users.show', $person)] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('Elm Street')
                ->assertDontSee('Marta');
        }
    }

    /* ── invites ─────────────────────────────────────────────────────────── */

    public function test_an_admin_can_mint_invites(): void
    {
        $this->admin();

        $this->post(route('admin.invites.store'), ['count' => 3, 'note' => 'round one'])
            ->assertRedirect();

        $this->assertSame(3, Invite::count());
    }

    /** An address-bound invite is for one person, so a count above 1 is wrong. */
    public function test_a_bound_invite_is_minted_once_however_many_were_asked_for(): void
    {
        $this->admin();

        $this->post(route('admin.invites.store'), ['count' => 20, 'email' => 'maya@escalate.test'])
            ->assertRedirect();

        $this->assertSame(1, Invite::count());
    }

    public function test_an_unclaimed_invite_can_be_withdrawn(): void
    {
        $invite = Invite::mint();
        $this->admin();

        $this->delete(route('admin.invites.destroy', $invite))->assertRedirect();

        $this->assertNull($invite->fresh());
    }

    /**
     * A used invite is kept: deleting it would not close the account it created
     * and would destroy the only record of how that person got in.
     */
    public function test_a_claimed_invite_is_not_deletable(): void
    {
        $invite = Invite::mint();
        $invite->claim($this->makeUser('holder@escalate.test'));

        $this->admin();

        $this->delete(route('admin.invites.destroy', $invite))->assertSessionHasErrors('invite');

        $this->assertNotNull($invite->fresh());
    }
}
