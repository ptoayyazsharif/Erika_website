<?php

namespace Tests\Feature;

use App\Mail\ApplicationReceived;
use App\Mail\ApplicationSubmitted;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Telling the administrators that somebody applied.
 *
 * Before this, an application landed in the database and announced itself to
 * nobody: the only way to find one was to remember to open the admin panel.
 *
 * What is worth asserting is not that mail goes out — that is one line — but
 * who it goes to and who it does not, that the answers survive the round trip
 * into a rendered message, and that none of it can cost somebody their
 * application if the mail server is having a bad day.
 */
class ApplicationNotifyTest extends TestCase
{
    use RefreshDatabase;

    private function answers(array $overrides = []): array
    {
        return array_merge([
            'name'          => 'Amara Okafor',
            'email'         => 'amara@example.test',
            'changing'      => 'Leaving a job I have outgrown.',
            'practice'      => 'I journal most mornings and pray.',
            'tried_apps'    => 'One, briefly. It felt like a to-do list.',
            'will_use'      => 'Yes, most days.',
            'will_feedback' => 'Yes, bluntly.',
            'agree'         => '1',
        ], $overrides);
    }

    private function admin(string $email): User
    {
        $user = $this->makeUser($email, 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        return $user->fresh();
    }

    public function test_every_admin_is_told_and_the_applicant_still_gets_a_receipt(): void
    {
        Mail::fake();

        $this->admin('one@escalate.test');
        $this->admin('two@escalate.test');
        $this->makeUser('member@escalate.test');       // not an admin

        $this->post(route('apply.store'), $this->answers())->assertRedirect();

        Mail::assertSent(ApplicationSubmitted::class, 2);
        Mail::assertSent(ApplicationSubmitted::class, fn ($m) => $m->hasTo('one@escalate.test'));
        Mail::assertSent(ApplicationSubmitted::class, fn ($m) => $m->hasTo('two@escalate.test'));
        Mail::assertNotSent(ApplicationSubmitted::class, fn ($m) => $m->hasTo('member@escalate.test'));

        // The promise made on the form itself is unaffected.
        Mail::assertSent(ApplicationReceived::class, fn ($m) => $m->hasTo('amara@example.test'));
    }

    /**
     * Suspending an administrator is how their access is taken away. Continuing
     * to post applicants' answers to their inbox would leave open exactly the
     * door the admin panel has already shut — and it would do so invisibly.
     */
    public function test_a_suspended_admin_is_not_told(): void
    {
        Mail::fake();

        $this->admin('active@escalate.test');
        $this->admin('gone@escalate.test')->forceFill(['suspended_at' => now()])->save();

        $this->post(route('apply.store'), $this->answers())->assertRedirect();

        Mail::assertSent(ApplicationSubmitted::class, 1);
        Mail::assertNotSent(ApplicationSubmitted::class, fn ($m) => $m->hasTo('gone@escalate.test'));
    }

    /**
     * store() lets somebody re-apply and replaces their answers in place. An
     * admin who read the first version needs to be told the second is
     * different, rather than getting a second mail claiming to be new.
     */
    public function test_re_applying_says_updated_rather_than_new(): void
    {
        Mail::fake();
        $this->admin('one@escalate.test');

        $this->post(route('apply.store'), $this->answers());
        Mail::assertSent(ApplicationSubmitted::class, fn ($m) => $m->isUpdate === false);

        $this->post(route('apply.store'), $this->answers(['changing' => 'Something else entirely.']));

        Mail::assertSent(ApplicationSubmitted::class, fn ($m) => $m->isUpdate === true);
        Mail::assertSent(ApplicationSubmitted::class, 2);
    }

    public function test_re_applying_after_a_decision_tells_nobody(): void
    {
        $this->admin('one@escalate.test');
        $this->post(route('apply.store'), $this->answers());

        Application::first()->forceFill(['status' => Application::SELECTED])->save();

        Mail::fake();
        $this->post(route('apply.store'), $this->answers(['changing' => 'Trying again.']));

        Mail::assertNothingSent();
    }

    public function test_the_switch_turns_it_off_without_touching_the_receipt(): void
    {
        Mail::fake();
        Config::set('escalate.beta.notify_admins', false);
        $this->admin('one@escalate.test');

        $this->post(route('apply.store'), $this->answers())->assertRedirect();

        Mail::assertNotSent(ApplicationSubmitted::class);
        Mail::assertSent(ApplicationReceived::class);
    }

    /**
     * A Mailable that throws while rendering fails inside Mailer's catch and
     * vanishes into the log, so "it was sent" is not evidence anybody could
     * read it. Build the real message and look at it.
     */
    public function test_the_message_actually_renders_with_the_answers_and_a_way_in(): void
    {
        $this->post(route('apply.store'), $this->answers());
        $application = Application::first();

        $body = (new ApplicationSubmitted($application))->render();

        $this->assertStringContainsString('Amara Okafor', $body);
        $this->assertStringContainsString('amara@example.test', $body);

        // The answer, and the question it was given to — both, because a list
        // of answers with no questions is not reviewable.
        $this->assertStringContainsString('Leaving a job I have outgrown.', $body);
        $this->assertStringContainsString('Yes, bluntly.', $body);
        $this->assertStringContainsString(
            e(\App\Support\Copy::question('changing')),
            $body,
        );

        $this->assertStringContainsString(
            route('admin.applications.show', $application),
            $body,
        );
    }

    /**
     * The guarantee App\Support\Mailer exists for, now with more sends behind
     * it: a transport fault must never discard somebody's application.
     */
    public function test_a_mail_failure_still_cannot_lose_an_application(): void
    {
        $this->admin('one@escalate.test');

        // `log` is the one mailer Mailer refuses outright, which exercises the
        // early return; the catch below it is exercised by the same path in
        // ApplicationTest. Either way the row must survive.
        Config::set('mail.default', 'log');

        $this->post(route('apply.store'), $this->answers())
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseCount('applications', 1);
        $this->assertSame('Amara Okafor', Application::first()->name);
    }
}
