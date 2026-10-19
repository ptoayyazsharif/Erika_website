<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Support\Push;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Notification keys, set from the admin panel instead of the server environment.
 *
 * They lived in Coolify env vars, which meant generating a pair, a deploy and
 * me. They are ordinary settings now — and better held here than there, because
 * the settings table encrypts its values while a container's environment is
 * plaintext to anything that can read it.
 *
 * The assertion with teeth is the last one: pressing the button a second time
 * kills every existing subscription, and the only reason that is acceptable is
 * that the browser repairs itself. This asserts the server half of that pair —
 * the rows go, and the count is reported rather than hidden.
 */
class PushKeysTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'keys@escalate.test'): User
    {
        $user = $this->makeUser($email, 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        return $user->fresh();
    }

    private function subscribe(User $user, string $endpoint): void
    {
        $s = new PushSubscription;
        $s->forceFill([
            'user_id'       => $user->id,
            'endpoint'      => $endpoint,
            'endpoint_hash' => PushSubscription::hash($endpoint),
            'p256dh'        => 'k',
            'auth'          => 'a',
            'timezone'      => 'Europe/London',
        ])->save();
    }

    private function withoutKeys(): void
    {
        Config::set('escalate.push.public_key', null);
        Config::set('escalate.push.private_key', null);
    }

    /* ── generating ──────────────────────────────────────────────────────── */

    public function test_an_admin_can_generate_a_pair_and_push_becomes_configured(): void
    {
        $this->withoutKeys();
        $this->assertFalse(Push::configured());

        $this->actingAs($this->admin())
            ->post(route('admin.settings.push-keys'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue(Settings::isOverridden('escalate.push.public_key'));
        $this->assertTrue(Settings::isOverridden('escalate.push.private_key'));

        // Settings::apply() overlays the rows onto config at boot, so the next
        // request sees them. Applied here directly rather than asserted on the
        // rows alone, because "stored" and "in use" are different claims.
        Settings::apply();
        $this->assertTrue(Push::configured());
    }

    /** A P-256 public key is 65 bytes, base64url — not a placeholder string. */
    public function test_the_generated_public_key_is_a_real_one(): void
    {
        $this->withoutKeys();
        $this->actingAs($this->admin())->post(route('admin.settings.push-keys'));

        Settings::apply();
        $public = (string) config('escalate.push.public_key');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $public);
        $this->assertSame(65, strlen(base64_decode(strtr($public, '-_', '+/'), true) ?: ''));
    }

    /**
     * The private half never comes back out.
     *
     * It is stored encrypted, like every setting, and marked secret so the form
     * renders an empty box rather than the value. An administrator can replace
     * a key without ever being shown one.
     */
    public function test_the_private_key_is_never_rendered_but_the_public_one_is(): void
    {
        $this->withoutKeys();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.settings.push-keys'));

        Settings::apply();
        $private = (string) config('escalate.push.private_key');
        $public = (string) config('escalate.push.public_key');

        $this->assertTrue(Settings::isSecret('escalate.push.private_key'));
        $this->assertFalse(Settings::isSecret('escalate.push.public_key'));

        $html = $this->actingAs($admin)
            ->get(route('admin.settings.section', ['section' => 'reminders']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($private, $html);
        $this->assertStringContainsString($public, $html);
    }

    /** Encrypted at rest, like everything else in that table. */
    public function test_the_private_key_is_not_in_the_database_in_the_clear(): void
    {
        $this->withoutKeys();
        $this->actingAs($this->admin())->post(route('admin.settings.push-keys'));

        Settings::apply();
        $private = (string) config('escalate.push.private_key');

        $raw = \Illuminate\Support\Facades\DB::table('settings')
            ->where('key', 'escalate.push.private_key')->value('value');

        $this->assertNotSame($private, $raw);
        $this->assertStringNotContainsString($private, (string) $raw);
    }

    /* ── it can only be pressed once ─────────────────────────────────────── */

    /**
     * The assertion the removed button is replaced by.
     *
     * Every device is bound to the public key it subscribed with, and the push
     * service rejects a send signed by any other pair — with a 403, which is
     * NOT one of the codes App\Support\Push prunes on. So a second pair
     * silently unreaches the whole beta at once.
     *
     * The screen no longer offers that, and this asserts the route refuses it
     * too: hiding a button is not removing a hazard, and a bookmark or a double
     * submit still arrives here.
     */
    public function test_a_second_press_is_refused_and_changes_nothing(): void
    {
        $this->withoutKeys();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.settings.push-keys'));
        Settings::apply();

        $public = (string) config('escalate.push.public_key');
        $this->subscribe($admin, 'https://fcm.googleapis.com/wp/phone');
        $this->subscribe($admin, 'https://fcm.googleapis.com/wp/laptop');

        $this->actingAs($admin)
            ->post(route('admin.settings.push-keys'))
            ->assertSessionHasErrors('push');

        // The pair is untouched and the devices are still reachable.
        $this->assertSame($public, Setting::query()->where('key', 'escalate.push.public_key')->first()?->value);
        $this->assertDatabaseCount('push_subscriptions', 2);
    }

    /** And the screen does not offer it, which is the half people see. */
    public function test_the_screen_offers_the_button_only_before_there_are_keys(): void
    {
        $this->withoutKeys();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.section', ['section' => 'reminders']))
            ->assertOk()
            ->assertSee('Generate the keys');

        $this->actingAs($admin)->post(route('admin.settings.push-keys'));
        Settings::apply();

        $this->actingAs($admin)
            ->get(route('admin.settings.section', ['section' => 'reminders']))
            ->assertOk()
            ->assertDontSee('Generate the keys')
            ->assertDontSee('Generate a new pair');
    }

    /** A first press has nothing to forget and must not claim otherwise. */
    public function test_a_first_press_does_not_talk_about_devices(): void
    {
        $this->withoutKeys();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.push-keys'))
            ->assertSessionHas('status', fn ($s) => ! str_contains($s, 'device'));
    }

    /**
     * A row left over from before the keys were cleared is dead too.
     *
     * The rule is the new public key, not the previous configured state: the
     * pair is random, so nothing that subscribed under any earlier one can be
     * reached. Guarding on "were keys set before" left orphans behind and the
     * Announcements screen counted them as an audience.
     */
    public function test_orphaned_devices_go_even_when_no_keys_were_configured(): void
    {
        $this->withoutKeys();
        $admin = $this->admin();
        $this->subscribe($admin, 'https://fcm.googleapis.com/wp/orphan');

        $this->actingAs($admin)
            ->post(route('admin.settings.push-keys'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, '1 device'));

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    /* ── the counts on the screen ────────────────────────────────────────── */

    /**
     * Three devices belonging to one person read as both numbers.
     *
     * "3" alone is true and misleading in a beta of twenty-five: it reads as
     * three testers, when it is one person with a phone, a laptop and a tablet.
     */
    public function test_devices_and_people_are_both_reported(): void
    {
        $this->withoutKeys();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.settings.push-keys'));
        Settings::apply();

        foreach (['phone', 'laptop', 'tablet'] as $device) {
            $this->subscribe($admin, "https://fcm.googleapis.com/wp/{$device}");
        }

        $this->actingAs($admin)
            ->get(route('admin.settings.section', ['section' => 'reminders']))
            ->assertOk()
            ->assertSee('3 devices')
            ->assertSee('1 person');

        $this->actingAs($admin)
            ->get(route('admin.announcements'))
            ->assertOk()
            ->assertSee('3 devices', false)
            ->assertSee('1 person', false);
    }

    /* ── saving the page must not half-break the pair ────────────────────── */

    /**
     * The trap this exists for.
     *
     * The private key is `secret`, so a blank box means "keep it". The public
     * key is shown, so without `keep_when_blank` a blank box would DELETE it —
     * leaving a stored private key, no public one, and push silently reaching
     * nobody. Half a keypair is worse than none, because nothing looks wrong.
     */
    public function test_saving_reminders_with_both_key_boxes_empty_keeps_the_pair(): void
    {
        $this->withoutKeys();
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.settings.push-keys'));

        Settings::apply();
        $public = (string) config('escalate.push.public_key');
        $private = (string) config('escalate.push.private_key');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'section'  => 'reminders',
            'settings' => [
                'escalate__push__enabled'     => '1',
                'escalate__push__hour'        => '9',
                'escalate__push__title'       => 'Escalate',
                'escalate__push__body'        => 'A few minutes for today?',
                'escalate__push__public_key'  => '',
                'escalate__push__private_key' => '',
            ],
        ])->assertRedirect();

        // Asserted on the stored rows, not on config(). Settings::apply()
        // overlays rows onto config and never puts a deleted one back within
        // the same process, so a config read here would happily return the key
        // this test is trying to prove was not thrown away — which it did, and
        // the test passed with the protection removed until this was fixed.
        $this->assertSame($public, Setting::query()->where('key', 'escalate.push.public_key')->first()?->value);
        $this->assertSame($private, Setting::query()->where('key', 'escalate.push.private_key')->first()?->value);
    }

    /** Typing a pair in by hand still works — a server minted elsewhere. */
    public function test_a_public_key_typed_in_by_hand_is_saved(): void
    {
        $this->withoutKeys();

        $this->actingAs($this->admin())->put(route('admin.settings.update'), [
            'section'  => 'reminders',
            'settings' => [
                'escalate__push__enabled'    => '1',
                'escalate__push__hour'       => '9',
                'escalate__push__title'      => 'Escalate',
                'escalate__push__body'       => 'A few minutes for today?',
                'escalate__push__public_key' => 'BOhandwrittenkey',
            ],
        ])->assertRedirect();

        $this->assertSame('BOhandwrittenkey', Setting::query()->where('key', 'escalate.push.public_key')->first()?->value);
    }

    /* ── who may press it ────────────────────────────────────────────────── */

    public function test_the_button_is_a_404_to_everybody_else(): void
    {
        $this->actingAs($this->makeUser('nosy@escalate.test'))
            ->post(route('admin.settings.push-keys'))
            ->assertNotFound();

        $this->assertDatabaseCount('settings', 0);
    }

    /** Nobody signed out can mint keys, and nothing is written trying. */
    public function test_a_stranger_cannot_press_it(): void
    {
        $this->post(route('admin.settings.push-keys'))->assertRedirect(route('login'));

        $this->assertSame(0, Setting::query()->count());
    }
}
