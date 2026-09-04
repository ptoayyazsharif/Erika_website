<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Support\DueReminders;
use App\Support\Push;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The daily reminder.
 *
 * What is asserted here is everything except the last hop. Delivery to a real
 * phone needs a real push service and a real device, neither of which exists in
 * this sandbox — so the payload, the pruning, the opt-outs and the hour
 * arithmetic are tested properly, and the final "did it buzz" is a thing a
 * human has to confirm once. Said plainly rather than implied away.
 */
class PushReminderTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(User $user, string $endpoint, ?string $timezone = 'Europe/London'): PushSubscription
    {
        $s = new PushSubscription;
        $s->forceFill([
            'user_id'       => $user->id,
            'endpoint'      => $endpoint,
            'endpoint_hash' => PushSubscription::hash($endpoint),
            'p256dh'        => 'a-public-key',
            'auth'          => 'an-auth-token',
            'timezone'      => $timezone,
        ])->save();

        return $s->fresh();
    }

    /* ── the endpoint is untrusted input ─────────────────────────────────── */

    /**
     * The assertion with teeth.
     *
     * An endpoint is a URL this server later makes outbound requests to, on a
     * schedule. Accepting an arbitrary one turns a signed-in account into
     * server-side request forgery with a cron attached — pointed at a cloud
     * metadata address or something on the private network behind this app.
     * The allowlist is the whole defence.
     */
    public function test_an_endpoint_that_is_not_a_real_push_service_is_refused(): void
    {
        $user = $this->makeUser('pusher@escalate.test');

        $bad = [
            'https://evil.test/collect',
            'http://fcm.googleapis.com/wp/x',                       // not https
            'https://169.254.169.254/latest/meta-data/',            // cloud metadata
            'https://evil.test/?x=https://fcm.googleapis.com',      // suffix in a query
            'https://fcm.googleapis.com.evil.test/wp/x',            // lookalike host
        ];

        foreach ($bad as $endpoint) {
            $this->actingAs($user)
                ->postJson(route('push.store'), [
                    'endpoint' => $endpoint,
                    'p256dh'   => 'k',
                    'auth'     => 'a',
                ])
                ->assertStatus(422);
        }

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_the_real_push_services_are_accepted(): void
    {
        $user = $this->makeUser('ok@escalate.test');

        foreach ([
            'https://fcm.googleapis.com/wp/abc',
            'https://updates.push.services.mozilla.com/wpush/v2/abc',
            'https://web.push.apple.com/abc',
        ] as $endpoint) {
            $this->actingAs($user)
                ->postJson(route('push.store'), [
                    'endpoint' => $endpoint,
                    'p256dh'   => 'k',
                    'auth'     => 'a',
                    'timezone' => 'Europe/London',
                ])
                ->assertOk();
        }

        $this->assertDatabaseCount('push_subscriptions', 3);
    }

    /** A zone the scheduler later hands to Carbon must be a real one. */
    public function test_a_nonsense_timezone_is_dropped_rather_than_stored(): void
    {
        $user = $this->makeUser('tz@escalate.test');

        $this->actingAs($user)->postJson(route('push.store'), [
            'endpoint' => 'https://fcm.googleapis.com/wp/tz',
            'p256dh'   => 'k',
            'auth'     => 'a',
            'timezone' => 'Mars/Olympus_Mons',
        ])->assertOk();

        $this->assertNull(PushSubscription::first()->timezone);
    }

    /** The same device re-subscribing is one row, not two. */
    public function test_resubscribing_the_same_device_does_not_duplicate_it(): void
    {
        $user = $this->makeUser('again@escalate.test');
        $payload = [
            'endpoint' => 'https://fcm.googleapis.com/wp/same',
            'p256dh'   => 'k',
            'auth'     => 'a',
        ];

        $this->actingAs($user)->postJson(route('push.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('push.store'), $payload)->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    /* ── who is due ──────────────────────────────────────────────────────── */

    /* ── who is due ──────────────────────────────────────────────────────── */

    public function test_the_hour_is_local_to_each_device(): void
    {
        // 09:00 in London is 17:00 in Tokyo, so exactly one of these is due.
        Carbon::setTestNow(Carbon::parse('2026-09-04 08:00:00', 'UTC'));

        $london = $this->subscribe($this->makeUser('london@escalate.test'), 'https://fcm.googleapis.com/wp/l', 'Europe/London');
        $this->subscribe($this->makeUser('tokyo@escalate.test'), 'https://fcm.googleapis.com/wp/t', 'Asia/Tokyo');

        $due = DueReminders::forHour(9);

        $this->assertCount(1, $due);
        $this->assertTrue($due->first()->is($london));

        Carbon::setTestNow();
    }

    public function test_somebody_who_was_already_here_today_is_not_nudged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 09:00:00', 'UTC'));

        $quiet = $this->makeUser('quiet@escalate.test');
        $active = $this->makeUser('active@escalate.test');

        $this->subscribe($quiet, 'https://fcm.googleapis.com/wp/q', 'UTC');
        $this->subscribe($active, 'https://fcm.googleapis.com/wp/a', 'UTC');

        DB::table('activity_days')->insert(['user_id' => $active->id, 'day' => now()->toDateString()]);

        $due = DueReminders::forHour(9);

        $this->assertCount(1, $due);
        $this->assertSame($quiet->id, $due->first()->user_id);

        Carbon::setTestNow();
    }

    public function test_somebody_who_switched_reminders_off_is_not_nudged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 09:00:00', 'UTC'));

        $off = $this->makeUser('off2@escalate.test');
        $off->profile->forceFill(['push_reminders' => false])->save();
        $this->subscribe($off, 'https://fcm.googleapis.com/wp/off', 'UTC');

        $this->assertCount(0, DueReminders::forHour(9));

        Carbon::setTestNow();
    }

    public function test_a_suspended_account_is_not_nudged(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 09:00:00', 'UTC'));

        $user = $this->makeUser('susp@escalate.test');
        $this->subscribe($user, 'https://fcm.googleapis.com/wp/s', 'UTC');
        $user->forceFill(['suspended_at' => now()])->save();

        $this->assertCount(0, DueReminders::forHour(9));

        Carbon::setTestNow();
    }

    /**
     * A timezone that no longer exists must not stop everybody else's reminder.
     * Stored zones are validated on the way in, but the database outlives the
     * tzdata in any given image.
     */
    public function test_a_broken_timezone_skips_one_device_and_not_the_rest(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-04 09:00:00', 'UTC'));

        $fine = $this->makeUser('fine@escalate.test');
        $this->subscribe($fine, 'https://fcm.googleapis.com/wp/fine', 'UTC');

        $broken = $this->subscribe($this->makeUser('broken@escalate.test'), 'https://fcm.googleapis.com/wp/broken', 'UTC');
        DB::table('push_subscriptions')->where('id', $broken->id)
            ->update(['timezone' => 'Mars/Olympus_Mons']);

        $due = DueReminders::forHour(9);

        $this->assertCount(1, $due);
        $this->assertSame($fine->id, $due->first()->user_id);

        Carbon::setTestNow();
    }

    public function test_the_command_says_so_and_stops_when_there_are_no_keys(): void
    {
        Config::set('escalate.push.public_key', '');
        Config::set('escalate.push.private_key', '');

        $this->artisan('escalate:remind')
            ->expectsOutputToContain('No VAPID keys')
            ->assertSuccessful();
    }

    public function test_the_command_does_nothing_when_reminders_are_switched_off(): void
    {
        Config::set('escalate.push.enabled', false);

        $this->artisan('escalate:remind')
            ->expectsOutputToContain('switched off')
            ->assertSuccessful();
    }

    public function test_push_is_inert_without_keys(): void
    {
        Config::set('escalate.push.public_key', '');

        $this->assertFalse(Push::configured());
        $this->assertSame(
            ['sent' => 0, 'pruned' => 0, 'failed' => 0],
            Push::send([], 'x', 'y', 'https://escalate.cloud'),
        );
    }

    /* ── switching it off ────────────────────────────────────────────────── */

    /**
     * Turning it off must actually delete the devices, not just set a flag.
     * A flag alone leaves endpoints that would start buzzing again the moment
     * anybody read the wrong column.
     */
    public function test_turning_reminders_off_forgets_every_device(): void
    {
        $user = $this->makeUser('off@escalate.test');
        $this->subscribe($user, 'https://fcm.googleapis.com/wp/x');
        $this->subscribe($user, 'https://fcm.googleapis.com/wp/y');

        $this->actingAs($user)->post(route('push.preference'), ['push_reminders' => '0'])
            ->assertRedirect();

        $this->assertFalse((bool) $user->fresh()->profile->push_reminders);
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_one_device_can_stand_down_without_silencing_the_others(): void
    {
        $user = $this->makeUser('one@escalate.test');
        $this->subscribe($user, 'https://fcm.googleapis.com/wp/phone');
        $this->subscribe($user, 'https://fcm.googleapis.com/wp/laptop');

        $this->actingAs($user)->postJson(route('push.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/wp/phone',
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint_hash' => PushSubscription::hash('https://fcm.googleapis.com/wp/laptop'),
        ]);
    }

    /** Somebody else's device is not theirs to remove. */
    public function test_you_cannot_unsubscribe_another_person_s_device(): void
    {
        $mine = $this->makeUser('mine@escalate.test');
        $theirs = $this->makeUser('theirs@escalate.test');
        $this->subscribe($theirs, 'https://fcm.googleapis.com/wp/theirs');

        $this->actingAs($mine)->postJson(route('push.destroy'), [
            'endpoint' => 'https://fcm.googleapis.com/wp/theirs',
        ])->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
    }

    /* ── the account ─────────────────────────────────────────────────────── */

    public function test_deleting_an_account_takes_its_devices_with_it(): void
    {
        $user = $this->makeUser('gone@escalate.test');
        $this->subscribe($user, 'https://fcm.googleapis.com/wp/gone');

        app(\App\Services\AccountEraser::class)->erase($user);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_the_preference_is_in_the_data_export(): void
    {
        $user = $this->makeUser('export@escalate.test');

        $export = app(\App\Services\AccountEraser::class)->export($user);

        $this->assertArrayHasKey('daily_reminders', $export['my_world']);
    }
}
