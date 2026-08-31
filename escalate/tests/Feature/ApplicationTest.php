<?php

namespace Tests\Feature;

use App\Mail\ApplicationReceived;
use App\Mail\ApplicationSelected;
use App\Models\Application;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The private beta application: the top of the funnel.
 *
 * This is the only form in the application with no invite gate in front of it —
 * unauthenticated, writes a row, sends mail. It is the same class of surface as
 * /register and /forgot-password, both of which had to be hardened, so it is
 * tested for the same things rather than only for the happy path.
 */
class ApplicationTest extends TestCase
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

    private function admin(): User
    {
        $user = $this->makeUser('appadmin@escalate.test', 'Admin');
        $user->forceFill(['role' => 'admin'])->save();

        $this->actingAs($user->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp]);

        return $user->fresh();
    }

    /* ── applying ────────────────────────────────────────────────────────── */

    public function test_the_form_is_public_and_reachable_without_an_account(): void
    {
        $this->get(route('apply'))->assertOk()->assertSee('Request private access');
    }

    public function test_an_application_is_recorded_and_acknowledged(): void
    {
        Mail::fake();
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), $this->answers())
            ->assertRedirect(route('apply'))
            ->assertSessionHasNoErrors();

        $application = Application::first();

        $this->assertNotNull($application);
        $this->assertSame('amara@example.test', $application->email);
        $this->assertSame('Leaving a job I have outgrown.', $application->changing);
        $this->assertSame(Application::PENDING, $application->status);

        Mail::assertSent(ApplicationReceived::class);
    }

    /** The answers are journal-grade, so they are stored the way a journal is. */
    public function test_the_answers_are_encrypted_at_rest(): void
    {
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), $this->answers());

        $raw = \DB::table('applications')->where('email', 'amara@example.test')->first();

        $this->assertStringNotContainsString('outgrown', $raw->changing);
        $this->assertSame('Leaving a job I have outgrown.', Application::first()->changing);
    }

    /**
     * Applying twice must not answer "you already applied".
     *
     * The register form had exactly this leak and it was closed there; a public
     * form that confirms membership of a manifestation beta is the same
     * disclosure wearing a different hat.
     */
    public function test_a_second_application_says_the_same_thing_as_the_first(): void
    {
        Config::set('mail.default', 'array');

        $first = $this->post(route('apply.store'), $this->answers());
        $second = $this->post(route('apply.store'), $this->answers(['changing' => 'A better answer.']));

        $this->assertSame($first->getStatusCode(), $second->getStatusCode());
        $this->assertSame(
            $first->getSession()->get('status'),
            $second->getSession()->get('status'),
        );

        // One row, updated — not two, and not a validation error.
        $this->assertSame(1, Application::count());
        $this->assertSame('A better answer.', Application::first()->changing);
    }

    /** A decision already made is not undone by re-applying. */
    public function test_re_applying_cannot_reset_a_decided_application(): void
    {
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), $this->answers());

        $application = Application::first();
        $application->forceFill(['status' => Application::WAITLISTED])->save();

        $this->post(route('apply.store'), $this->answers(['changing' => 'Let me back in.']));

        $this->assertSame(Application::WAITLISTED, Application::first()->status);
        $this->assertNotSame('Let me back in.', Application::first()->changing);
    }

    public function test_the_honeypot_swallows_a_bot_without_recording_anything(): void
    {
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), $this->answers(['website' => 'http://spam.test']))
            ->assertRedirect(route('apply'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Application::count());
    }

    public function test_every_question_is_required(): void
    {
        // Without the throttle: this walks eight fields, and the point here is
        // the validation rules rather than the rate limit, which has a test of
        // its own below.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        foreach (['name', 'email', 'changing', 'practice', 'tried_apps', 'will_use', 'will_feedback'] as $field) {
            $this->post(route('apply.store'), $this->answers([$field => '']))
                ->assertSessionHasErrors($field);
        }

        $this->post(route('apply.store'), $this->answers(['agree' => '']))
            ->assertSessionHasErrors('agree');

        $this->assertSame(0, Application::count());
    }

    /**
     * The crash family that cost five pages, on the newest public route.
     *
     * `?field[]=x` against an untyped read is what turned trim() into a 500 on
     * /register and /reset-password. This endpoint is unauthenticated too.
     */
    public function test_array_input_is_refused_rather_than_fatal(): void
    {
        foreach (['name', 'email', 'changing', 'website'] as $field) {
            $response = $this->post(route('apply.store'), $this->answers([$field => ['x']]));

            $this->assertLessThan(500, $response->getStatusCode(), "{$field}[] returned a server error.");
        }
    }

    /**
     * The endpoint is rate limited, and at a number a launch can survive.
     *
     * These limits key on IP and mobile carriers share one address between
     * thousands of people, so a limit tuned like /forgot-password's would turn
     * a successful Instagram post into a wall of 429s for real applicants.
     */
    public function test_the_form_is_throttled_but_not_so_tightly_it_blocks_a_launch(): void
    {
        Config::set('mail.default', 'array');

        $codes = [];

        foreach (range(1, 21) as $i) {
            $codes[] = $this->post(route('apply.store'), $this->answers([
                'email' => "applicant{$i}@example.test",
            ]))->getStatusCode();
        }

        // Twenty unrelated people from one carrier get through.
        $this->assertNotContains(429, array_slice($codes, 0, 20));

        // The twenty-first is held.
        $this->assertSame(429, $codes[20]);
    }

    /* ── deciding ────────────────────────────────────────────────────────── */

    public function test_selecting_mints_a_bound_invite_and_emails_the_code(): void
    {
        Mail::fake();
        Config::set('mail.default', 'array');
        Config::set('escalate.beta.invite_days', 30);

        $this->post(route('apply.store'), $this->answers());
        $application = Application::first();

        $this->admin();

        $this->post(route('admin.applications.select', $application))->assertRedirect();

        $application->refresh();

        $this->assertSame(Application::SELECTED, $application->status);
        $this->assertNotNull($application->decided_at);
        $this->assertNotNull($application->invite);

        // Bound to them: a seat is not transferable by forwarding the email.
        $this->assertSame('amara@example.test', $application->invite->email);
        $this->assertSame('Founding 25', $application->invite->note);

        Mail::assertSent(ApplicationSelected::class);
    }

    /** The code that arrives must actually open the door. */
    public function test_the_emailed_code_lets_that_person_register(): void
    {
        Config::set('mail.default', 'array');
        Config::set('escalate.beta.invite_only', true);

        $this->post(route('apply.store'), $this->answers());
        $application = Application::first();

        $this->admin();
        $this->post(route('admin.applications.select', $application));

        $code = $application->fresh()->invite->code;

        // Sign the admin out before walking through as the applicant.
        auth()->logout();
        session()->flush();

        $this->post(route('register.store'), [
            'name' => 'Amara Okafor',
            'email' => 'amara@example.test',
            'password' => 'a-long-enough-password-1',
            'password_confirmation' => 'a-long-enough-password-1',
            'agree' => '1', 'age' => '1', 'invite' => $code,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(User::where('email', 'amara@example.test')->first());
    }

    /** Declining is the waitlist, and it emails nobody. */
    public function test_declining_waitlists_them_silently(): void
    {
        Mail::fake();
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), $this->answers());
        $application = Application::first();

        $this->admin();
        $this->post(route('admin.applications.decline', $application))->assertRedirect();

        $this->assertSame(Application::WAITLISTED, $application->fresh()->status);
        $this->assertNull($application->fresh()->invite);

        Mail::assertNotSent(ApplicationSelected::class);
    }

    /** Deciding twice must not mint a second seat. */
    public function test_an_application_cannot_be_selected_twice(): void
    {
        Config::set('mail.default', 'array');

        $this->post(route('apply.store'), $this->answers());
        $application = Application::first();

        $this->admin();
        $this->post(route('admin.applications.select', $application));
        $this->post(route('admin.applications.select', $application))->assertSessionHasErrors('application');

        $this->assertSame(1, Invite::count());
    }

    /* ── who may read them ───────────────────────────────────────────────── */

    public function test_applications_are_invisible_to_everyone_but_an_admin(): void
    {
        Config::set('mail.default', 'array');
        $this->post(route('apply.store'), $this->answers());
        $application = Application::first();

        $this->actingAs($this->makeUser('nosy@escalate.test'));

        $this->get(route('admin.applications'))->assertNotFound();
        $this->get(route('admin.applications.show', $application))->assertNotFound();
        $this->post(route('admin.applications.select', $application))->assertNotFound();

        $this->assertSame(Application::PENDING, $application->fresh()->status);
    }
}
