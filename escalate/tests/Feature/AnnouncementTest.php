<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementEmail;
use App\Jobs\SendAnnouncementPush;
use App\Mail\AnnouncementMail;
use App\Mail\ApplicationSelected;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\Invite;
use App\Models\PushSubscription;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Announcements, and the unsubscribe that has to actually work.
 *
 * The assertions that matter most are the promise ones: an opt-out honoured
 * from a signed-out browser, and transactional mail that ignores the opt-out
 * because it is a reply to something the person did.
 */
class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'boss@escalate.test'): User
    {
        $user = $this->makeUser($email, 'Boss');
        $user->forceFill(['role' => 'admin'])->save();

        return $user->fresh();
    }

    private function announce(array $overrides = []): Announcement
    {
        $a = new Announcement;
        $a->forceFill(array_merge([
            'title'        => 'Affirmation cards are live',
            'body'         => 'Open **My Cards** to see today’s.',
            'show_in_app'  => true,
            'send_email'   => false,
            'published_at' => now(),
        ], $overrides))->save();

        return $a->fresh();
    }

    /* ── the banner ──────────────────────────────────────────────────────── */

    public function test_the_banner_shows_and_dismissing_sticks_for_that_person_only(): void
    {
        $a = $this->announce();
        $mine = $this->makeUser('mine@escalate.test');
        $other = $this->makeUser('other@escalate.test');

        $this->actingAs($mine)->get(route('today'))->assertOk()->assertSee('Affirmation cards are live');

        $this->actingAs($mine)->post(route('announcements.dismiss', $a))->assertRedirect();

        $this->actingAs($mine)->get(route('today'))->assertDontSee('Affirmation cards are live');

        // Somebody else has not closed it.
        $this->actingAs($other)->get(route('today'))->assertSee('Affirmation cards are live');
    }

    /** Two tabs, or a double tap, must not race into a constraint violation. */
    public function test_dismissing_twice_is_harmless(): void
    {
        $a = $this->announce();
        $user = $this->makeUser('twice@escalate.test');

        $this->actingAs($user)->post(route('announcements.dismiss', $a))->assertRedirect();
        $this->actingAs($user)->post(route('announcements.dismiss', $a))->assertRedirect();

        $this->assertDatabaseCount('announcement_dismissals', 1);
    }

    public function test_a_draft_shows_to_nobody(): void
    {
        $this->announce(['published_at' => null]);

        $this->actingAs($this->makeUser('reader@escalate.test'))
            ->get(route('today'))
            ->assertDontSee('Affirmation cards are live');
    }

    /**
     * An admin's Markdown reaches every user's browser, where the app's CSP is
     * the last line of defence. Asserted on parsed elements, not substrings:
     * escaped text legitimately still contains the characters.
     */
    public function test_an_admin_cannot_put_markup_into_everybody_s_browser(): void
    {
        $a = $this->announce(['body' => "<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>"]);

        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<div>'.$a->html().'</div>');

        $tags = array_map(fn ($n) => $n->nodeName, iterator_to_array($document->getElementsByTagName('*')));

        $this->assertNotContains('script', $tags);
        $this->assertNotContains('img', $tags);
        $this->assertStringContainsString('&lt;script&gt;', (string) $a->html());
    }

    /* ── the blast ───────────────────────────────────────────────────────── */

    public function test_sending_queues_one_job_per_person_and_skips_the_opted_out(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $in = $this->makeUser('in@escalate.test');
        $out = $this->makeUser('out@escalate.test');
        $out->profile->forceFill(['announcement_emails' => false])->save();

        $a = $this->announce(['send_email' => true]);

        $this->actingAs($admin)->post(route('admin.announcements.send', $a))->assertRedirect();

        // The admin and the opted-in user; not the one who opted out.
        Queue::assertPushed(SendAnnouncementEmail::class,
            fn ($job) => $job->recipient->is($in));
        Queue::assertNotPushed(SendAnnouncementEmail::class,
            fn ($job) => $job->recipient->is($out));
    }

    /** Emailing a hundred people twice is the failure with no undo. */
    public function test_pressing_send_twice_sends_once(): void
    {
        Queue::fake();

        $admin = $this->admin();
        $this->makeUser('reader@escalate.test');
        $a = $this->announce(['send_email' => true]);

        $this->actingAs($admin)->post(route('admin.announcements.send', $a))->assertRedirect();
        $first = Queue::pushed(SendAnnouncementEmail::class)->count();

        $this->actingAs($admin)->post(route('admin.announcements.send', $a))
            ->assertSessionHasErrors('announcement');

        $this->assertSame($first, Queue::pushed(SendAnnouncementEmail::class)->count());
    }

    /**
     * The window between dispatch and delivery is real. Somebody who clicks
     * unsubscribe while the queue is draining must not receive the mail.
     */
    public function test_the_job_rechecks_the_opt_out_at_delivery(): void
    {
        Mail::fake();

        $user = $this->makeUser('late@escalate.test');
        $a = $this->announce(['send_email' => true]);

        $job = new SendAnnouncementEmail($a, $user);

        $user->profile->forceFill(['announcement_emails' => false])->save();
        $job->handle();

        Mail::assertNothingSent();
    }

    /* ── the promise ─────────────────────────────────────────────────────── */

    public function test_unsubscribing_works_from_a_signed_out_browser(): void
    {
        $user = $this->makeUser('quiet@escalate.test');

        $this->get(URL::signedRoute('announcements.unsubscribe', ['user' => $user->id]))
            ->assertOk()
            ->assertSee('quiet@escalate.test');

        $this->assertFalse($user->fresh()->wantsAnnouncementEmails());
    }

    public function test_an_unsigned_or_tampered_link_is_refused(): void
    {
        $user = $this->makeUser('safe@escalate.test');
        $other = $this->makeUser('victim@escalate.test');

        // No signature at all.
        $this->get('/unsubscribe/'.$user->id)->assertForbidden();

        // A valid signature for one person, pointed at another.
        $signed = URL::signedRoute('announcements.unsubscribe', ['user' => $user->id]);
        $this->get(str_replace('/unsubscribe/'.$user->id, '/unsubscribe/'.$other->id, $signed))
            ->assertForbidden();

        $this->assertTrue($other->fresh()->wantsAnnouncementEmails());
    }

    public function test_the_undo_puts_them_back(): void
    {
        $user = $this->makeUser('oops@escalate.test');

        $this->get(URL::signedRoute('announcements.unsubscribe', ['user' => $user->id]));
        $this->assertFalse($user->fresh()->wantsAnnouncementEmails());

        $this->get(URL::signedRoute('announcements.resubscribe', ['user' => $user->id]))->assertOk();
        $this->assertTrue($user->fresh()->wantsAnnouncementEmails());
    }

    /**
     * The distinction the whole opt-out rests on. An invite is a reply to
     * something the person did; swallowing it because they opted out of a
     * newsletter would be the opt-out breaking the app.
     */
    public function test_transactional_mail_ignores_the_announcement_opt_out(): void
    {
        Mail::fake();

        $user = $this->makeUser('optedout@escalate.test');
        $user->profile->forceFill(['announcement_emails' => false])->save();

        $application = new Application;
        $application->forceFill([
            'name' => 'Opted Out', 'email' => 'optedout@escalate.test',
            'changing' => 'a', 'practice' => 'b', 'tried_apps' => 'c',
            'will_use' => 'd', 'will_feedback' => 'e', 'status' => Application::PENDING,
        ])->save();

        Mailer::send($user->email, new ApplicationSelected(
            $application->fresh(),
            Invite::mint($user->email, 'Founding 25', 30),
        ));

        Mail::assertSent(ApplicationSelected::class);
    }

    /** The email carries a working unsubscribe, or it should not be sent. */
    public function test_the_announcement_email_carries_an_unsubscribe_link(): void
    {
        $user = $this->makeUser('reader2@escalate.test');
        $html = (new AnnouncementMail($this->announce(), $user))->render();

        $this->assertStringContainsString('unsubscribe/'.$user->id, $html);
        $this->assertStringContainsString('signature=', $html);
    }

    /* ── who may do what ─────────────────────────────────────────────────── */

    public function test_the_admin_screen_is_a_404_to_everybody_else(): void
    {
        $this->actingAs($this->makeUser('nosy@escalate.test'))
            ->get(route('admin.announcements'))
            ->assertNotFound();

        $a = $this->announce();

        $this->actingAs($this->makeUser('nosy2@escalate.test'))
            ->post(route('admin.announcements.send', $a))
            ->assertNotFound();
    }

    public function test_an_admin_can_write_one(): void
    {
        $this->actingAs($this->admin())->post(route('admin.announcements.store'), [
            'title'       => 'The beta ends Friday',
            'body'        => 'Thank you — the survey is on Today.',
            'show_in_app' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('announcements', ['title' => 'The beta ends Friday']);
        $this->assertTrue(Announcement::first()->isPublished());
        $this->assertFalse(Announcement::first()->wasEmailed());
    }

    /* ── the third destination: a notification ───────────────────────────── */

    /** VAPID keys exist in this process, so Push::configured() is true. */
    private function withPushKeys(): void
    {
        Config::set('escalate.push.public_key', 'a-public-key');
        Config::set('escalate.push.private_key', 'a-private-key');
    }

    private function subscribe(User $user, string $endpoint): PushSubscription
    {
        $s = new PushSubscription;
        $s->forceFill([
            'user_id'       => $user->id,
            'endpoint'      => $endpoint,
            'endpoint_hash' => PushSubscription::hash($endpoint),
            'p256dh'        => 'a-public-key',
            'auth'          => 'an-auth-token',
            'timezone'      => 'Europe/London',
        ])->save();

        return $s->fresh();
    }

    /**
     * The guard, pressed twice.
     *
     * A duplicate email sits in an inbox looking like a duplicate. A duplicate
     * notification interrupts somebody a second time, which is how people
     * switch notifications off for good — so this is asserted by pressing the
     * button twice rather than by reading the code.
     */
    public function test_pushing_sends_once_and_a_second_press_sends_nothing(): void
    {
        Queue::fake();
        $this->withPushKeys();

        $a = $this->announce();
        $admin = $this->admin();
        $this->subscribe($this->makeUser('phone@escalate.test'), 'https://fcm.googleapis.com/wp/phone');

        $this->actingAs($admin)
            ->post(route('admin.announcements.push', $a))
            ->assertRedirect();

        Queue::assertPushed(SendAnnouncementPush::class, 1);
        $this->assertNotNull($a->fresh()->pushed_at);
        $this->assertTrue($a->fresh()->send_push);

        $this->actingAs($admin)
            ->post(route('admin.announcements.push', $a))
            ->assertSessionHasErrors('announcement');

        Queue::assertPushed(SendAnnouncementPush::class, 1);
    }

    /**
     * The deliberate difference from the daily reminder.
     *
     * DueReminders skips anybody already in the app today, which is right for a
     * nudge and wrong for news: somebody who wrote this morning still needs to
     * hear that the beta ends on Friday. Asserted so that merging the two
     * selections later fails here rather than quietly dropping the most engaged
     * testers from every announcement.
     */
    public function test_somebody_who_used_the_app_today_still_gets_the_announcement(): void
    {
        Queue::fake();
        $this->withPushKeys();

        $active = $this->makeUser('active@escalate.test');
        $this->subscribe($active, 'https://fcm.googleapis.com/wp/active');
        DB::table('activity_days')->insert(['user_id' => $active->id, 'day' => now()->toDateString()]);

        // The same person is not due a daily reminder…
        $this->assertCount(0, \App\Support\DueReminders::forHour(9, ignoreClock: true));

        // …and is still reachable by an announcement.
        $this->assertCount(1, PushSubscription::query()->reachable()->get());

        $this->actingAs($this->admin())
            ->post(route('admin.announcements.push', $this->announce()))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '1 device')
                && str_contains($status, '1 person'));
    }

    public function test_the_switch_off_and_a_suspension_are_both_honoured(): void
    {
        $this->withPushKeys();

        $off = $this->makeUser('pushoff@escalate.test');
        $off->profile->forceFill(['push_reminders' => false])->save();
        $this->subscribe($off, 'https://fcm.googleapis.com/wp/off');

        $suspended = $this->makeUser('gone@escalate.test');
        $this->subscribe($suspended, 'https://fcm.googleapis.com/wp/gone');
        $suspended->forceFill(['suspended_at' => now()])->save();

        $this->assertCount(0, PushSubscription::query()->reachable()->get());
    }

    /** A notification is one or two lines of text, not a document of Markdown. */
    public function test_the_notification_body_is_plain_text(): void
    {
        $a = $this->announce([
            'body' => "## Friday\n\nThe beta **ends** on [Friday](https://escalate.cloud) "
                .'— thank you & good luck. '.str_repeat('Words and more words. ', 20),
        ]);

        $body = $a->notificationBody();

        $this->assertStringNotContainsString('<', $body);
        $this->assertStringNotContainsString('**', $body);
        $this->assertStringNotContainsString('&amp;', $body);
        $this->assertStringContainsString('& good luck', $body);
        $this->assertStringContainsString('The beta ends on Friday', $body);
        $this->assertLessThanOrEqual(141, mb_strlen($body));
    }

    public function test_with_no_keys_nothing_is_sent_and_the_screen_says_so(): void
    {
        Queue::fake();
        Config::set('escalate.push.public_key', '');
        Config::set('escalate.push.private_key', '');

        $a = $this->announce();
        $admin = $this->admin();
        $this->subscribe($this->makeUser('phone2@escalate.test'), 'https://fcm.googleapis.com/wp/phone2');

        $this->actingAs($admin)
            ->post(route('admin.announcements.push', $a))
            ->assertSessionHasErrors('announcement');

        Queue::assertNotPushed(SendAnnouncementPush::class);
        $this->assertNull($a->fresh()->pushed_at);

        $this->actingAs($admin)
            ->get(route('admin.announcements'))
            ->assertSee('Notifications are not set up on this server');
    }

    /** Nothing to send to is refused rather than recorded as sent. */
    public function test_pushing_with_no_devices_does_not_burn_the_one_press(): void
    {
        Queue::fake();
        $this->withPushKeys();

        $a = $this->announce();

        $this->actingAs($this->admin())
            ->post(route('admin.announcements.push', $a))
            ->assertSessionHasErrors('announcement');

        $this->assertNull($a->fresh()->pushed_at);
        Queue::assertNotPushed(SendAnnouncementPush::class);
    }

    public function test_the_push_route_is_a_404_to_everybody_else(): void
    {
        $this->actingAs($this->makeUser('nosy3@escalate.test'))
            ->post(route('admin.announcements.push', $this->announce()))
            ->assertNotFound();
    }

    /** The job itself is inert without keys, wherever it is dispatched from. */
    public function test_the_job_sends_nothing_without_keys(): void
    {
        Config::set('escalate.push.public_key', '');

        $this->subscribe($this->makeUser('phone3@escalate.test'), 'https://fcm.googleapis.com/wp/phone3');

        (new SendAnnouncementPush($this->announce()))->handle();

        $this->assertDatabaseCount('push_subscriptions', 1);
    }
}
