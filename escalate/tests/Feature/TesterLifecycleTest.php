<?php

namespace Tests\Feature;

use App\Mail\AccessRevoked;
use App\Models\Application;
use App\Models\Invite;
use App\Models\User;
use App\Support\TesterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Who was let in, and where they got stuck.
 *
 * The status is derived, never stored, so what is asserted is that each step of
 * the real chain — application → invite → claim → story → activity — resolves
 * to the right answer, and that revoking is refused everywhere it would leave
 * an account behind with no invite explaining it.
 */
class TesterLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = $this->makeUser('boss@escalate.test', 'Boss');
        $user->forceFill(['role' => 'admin'])->save();

        return $user->fresh();
    }

    /** A selected application with an invite, optionally claimed. */
    private function selected(string $email, ?User $claimant = null, ?int $expiresInDays = 30): Application
    {
        $invite = Invite::mint($email, 'Founding 25', $expiresInDays);

        if ($claimant) {
            $invite->forceFill(['claimed_by' => $claimant->id, 'claimed_at' => now()])->save();
        }

        $application = new Application;
        $application->forceFill([
            'name' => 'Tester', 'email' => $email,
            'changing' => 'x', 'practice' => 'x', 'tried_apps' => 'x',
            'will_use' => 'x', 'will_feedback' => 'x',
            'status' => Application::SELECTED, 'decided_at' => now()->subDays(9),
            'invite_id' => $invite->id,
        ])->save();

        return $application->fresh(['invite.claimant']);
    }

    /* ── the derivation ──────────────────────────────────────────────────── */

    public function test_each_step_of_the_chain_reads_correctly(): void
    {
        $never = $this->selected('never@example.test');
        $this->assertSame(TesterStatus::INVITED, TesterStatus::of($never, null, false));

        $expired = $this->selected('expired@example.test', null, 30);
        $expired->invite->forceFill(['expires_at' => now()->subDay()])->save();
        $this->assertSame(TesterStatus::EXPIRED, TesterStatus::of($expired->fresh('invite'), null, false));

        $signedUp = $this->selected('quiet@example.test', $this->makeUser('quiet@example.test'));
        $this->assertSame(TesterStatus::SIGNED_UP, TesterStatus::of($signedUp, null, false));

        // Wrote something, but not here for a fortnight.
        $this->assertSame(TesterStatus::WRITING,
            TesterStatus::of($signedUp, Carbon::now()->subDays(14), true));

        // Here this week beats having written: they are doing the thing.
        $this->assertSame(TesterStatus::ACTIVE,
            TesterStatus::of($signedUp, Carbon::now()->subDay(), false));
    }

    /** A selected application with no invite can only mean it was taken back. */
    public function test_a_selected_application_without_an_invite_reads_as_revoked(): void
    {
        $a = $this->selected('gone@example.test');
        $a->invite->delete();

        $this->assertSame(TesterStatus::REVOKED, TesterStatus::of($a->fresh('invite'), null, false));
    }

    /* ── the screen ──────────────────────────────────────────────────────── */

    public function test_the_screen_lists_selected_testers_and_their_state(): void
    {
        $this->selected('never@example.test');

        $this->actingAs($this->admin())
            ->get(route('admin.testers'))
            ->assertOk()
            ->assertSee('never@example.test')
            ->assertSee('Invited, never signed up');
    }

    public function test_it_is_a_404_to_everybody_but_an_admin(): void
    {
        $this->actingAs($this->makeUser('curious@escalate.test'))
            ->get(route('admin.testers'))
            ->assertNotFound();
    }

    /* ── revoking ────────────────────────────────────────────────────────── */

    public function test_revoking_frees_the_seat_and_waitlists_them(): void
    {
        Mail::fake();
        $a = $this->selected('never@example.test');
        $inviteId = $a->invite->id;

        $this->actingAs($this->admin())
            ->post(route('admin.testers.revoke', $a))
            ->assertRedirect();

        $a->refresh();

        $this->assertSame(Application::WAITLISTED, $a->status);
        $this->assertNull($a->invite_id);
        $this->assertDatabaseMissing('invites', ['id' => $inviteId]);

        Mail::assertSent(AccessRevoked::class, fn ($m) => $m->hasTo('never@example.test'));
    }

    /**
     * The guard that matters. Revoking somebody who already has an account
     * would delete the invite that account was created from and leave it
     * unexplained. Suspend is the tool there, and it already exists.
     */
    public function test_somebody_who_already_signed_up_cannot_be_revoked(): void
    {
        Mail::fake();
        $user = $this->makeUser('joined@example.test');
        $a = $this->selected('joined@example.test', $user);

        $this->actingAs($this->admin())
            ->post(route('admin.testers.revoke', $a))
            ->assertSessionHasErrors('tester');

        $a->refresh();

        $this->assertSame(Application::SELECTED, $a->status);
        $this->assertNotNull($a->invite_id);
        Mail::assertNothingSent();
    }

    public function test_the_setting_decides_whether_they_are_told(): void
    {
        Mail::fake();
        Config::set('escalate.beta.notify_revoked', false);
        $a = $this->selected('never@example.test');

        $this->actingAs($this->admin())
            ->post(route('admin.testers.revoke', $a))
            ->assertRedirect();

        Mail::assertNothingSent();
        $this->assertSame(Application::WAITLISTED, $a->fresh()->status);
    }

    /**
     * The screen loads a row per tester; the facts behind each row are gathered
     * in two grouped queries, not two per person. Asserted because an N+1 here
     * degrades quietly — it works fine at 3 testers and not at 300.
     */
    public function test_the_screen_does_not_query_per_tester(): void
    {
        $admin = $this->admin();

        foreach (range(1, 6) as $i) {
            $this->selected("t{$i}@example.test", $this->makeUser("t{$i}@example.test"));
        }

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.testers'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(20, $count, "Ran {$count} queries for six testers — that is per-row work.");
    }
}
