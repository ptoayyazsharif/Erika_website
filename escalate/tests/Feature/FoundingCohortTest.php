<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ApplicationController as AdminApplications;
use App\Models\Application;
use App\Models\Invite;
use App\Models\User;
use App\Support\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The Founding 25.
 *
 * Two promises are made in the launch materials: a badge, and pricing that
 * does not change. Both are settled the moment the invite is claimed rather
 * than by somebody remembering to do it afterwards — a founding tester who
 * gets billed because a manual step was missed is the single failure this
 * cohort must not experience.
 */
class FoundingCohortTest extends TestCase
{
    use RefreshDatabase;

    private function registerWith(Invite $invite, string $email): void
    {
        Config::set('escalate.beta.invite_only', true);

        $this->post(route('register.store'), [
            'name' => 'Founding Tester',
            'email' => $email,
            'password' => 'a-long-enough-password-1',
            'password_confirmation' => 'a-long-enough-password-1',
            'agree' => '1', 'age' => '1', 'invite' => $invite->code,
        ])->assertSessionHasNoErrors();
    }

    public function test_claiming_a_founding_invite_records_the_cohort_and_comps_them(): void
    {
        $invite = Invite::mint('founder@escalate.test', AdminApplications::COHORT, 30);

        $this->registerWith($invite, 'founder@escalate.test');

        $user = User::where('email', 'founder@escalate.test')->firstOrFail();

        $this->assertSame('Founding 25', $user->cohort);
        $this->assertSame(Plan::paidKey(), $user->plan_override);
    }

    /** And the comp is real: they are on the full plan with billing switched on. */
    public function test_a_founding_tester_is_on_the_full_plan_without_paying(): void
    {
        Config::set('escalate.billing.enabled', true);

        $invite = Invite::mint('paid@escalate.test', AdminApplications::COHORT, 30);
        $this->registerWith($invite, 'paid@escalate.test');

        $user = User::where('email', 'paid@escalate.test')->firstOrFail();

        $this->assertNotSame(Plan::FREE, $user->planKey());
        $this->assertFalse($user->subscribed(), 'They should owe Stripe nothing.');
        $this->assertGreaterThan(1, Plan::quota($user, 'story'));
    }

    /** An ordinary invite grants no badge and no comp. */
    public function test_an_ordinary_invite_changes_nothing(): void
    {
        $invite = Invite::mint('ordinary@escalate.test', 'beta round two', 30);

        $this->registerWith($invite, 'ordinary@escalate.test');

        $user = User::where('email', 'ordinary@escalate.test')->firstOrFail();

        $this->assertNull($user->cohort);
        $this->assertNull($user->plan_override);
    }

    /** The badge is shown to the person it belongs to. */
    public function test_the_badge_appears_on_their_own_plan_page(): void
    {
        Config::set('escalate.billing.enabled', true);

        $user = $this->makeUser('badge@escalate.test');
        $user->forceFill(['cohort' => AdminApplications::COHORT])->save();

        $this->actingAs($user->fresh())
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Founding 25')
            ->assertSee('there is nothing to pay');
    }

    /** And to whoever is deciding, on the admin People screen. */
    public function test_the_cohort_is_visible_to_an_admin(): void
    {
        $person = $this->makeUser('shown@escalate.test');
        $person->forceFill(['cohort' => AdminApplications::COHORT])->save();

        $admin = $this->makeUser('cohortadmin@escalate.test', 'Admin');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp])
            ->get(route('admin.users.show', $person))
            ->assertOk()
            ->assertSee('Founding 25');
    }

    /**
     * End to end: apply, be selected, use the code, arrive comped.
     *
     * The whole promise in one test, because it crosses four files and each of
     * them is individually correct in ways that would not add up.
     */
    public function test_an_applicant_selected_by_an_admin_arrives_as_a_founding_member(): void
    {
        Config::set('mail.default', 'array');
        Config::set('escalate.billing.enabled', true);

        $this->post(route('apply.store'), [
            'name' => 'Founding Tester', 'email' => 'endtoend@escalate.test',
            'changing' => 'A thing.', 'practice' => 'Sometimes.', 'tried_apps' => 'No.',
            'will_use' => 'Yes.', 'will_feedback' => 'Yes.', 'agree' => '1',
        ]);

        $admin = $this->makeUser('e2eadmin@escalate.test', 'Admin');
        $admin->forceFill(['role' => 'admin'])->save();

        $this->actingAs($admin->fresh())
            ->withSession(['admin.verified' => true, 'admin.verified_at' => now()->timestamp])
            ->post(route('admin.applications.select', Application::first()));

        auth()->logout();
        session()->flush();

        $this->registerWith(Application::first()->fresh()->invite, 'endtoend@escalate.test');

        $user = User::where('email', 'endtoend@escalate.test')->firstOrFail();

        $this->assertSame('Founding 25', $user->cohort);
        $this->assertNotSame(Plan::FREE, $user->planKey());
    }
}
