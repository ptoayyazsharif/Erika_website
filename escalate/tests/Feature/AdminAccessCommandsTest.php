<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The commands that get a locked-out owner back into the admin area.
 *
 * These are the only route in: there is no web form that grants the admin
 * role, deliberately, so a shell is the price of privilege escalation.
 */
class AdminAccessCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_accounts_with_their_roles(): void
    {
        $this->makeUser('owner@escalate.test', 'Owner')
            ->forceFill(['role' => 'admin'])->save();

        $this->makeUser('someone@escalate.test', 'Someone');

        $this->artisan('escalate:users')
            ->expectsOutputToContain('owner@escalate.test')
            ->expectsOutputToContain('someone@escalate.test')
            ->expectsOutputToContain('1 admin, 2 accounts in total.')
            ->assertSuccessful();
    }

    /**
     * The state that started all this: an app with users and no admin.
     *
     * "No accounts yet" would be a lie and "1 admin" a worse one, so the
     * command says plainly that nobody can get in, and how to change it.
     */
    public function test_it_says_so_when_nobody_can_reach_the_admin_area(): void
    {
        $this->makeUser('alone@escalate.test');

        $this->artisan('escalate:users')
            ->expectsOutputToContain('Nobody can reach /admin')
            ->expectsOutputToContain('escalate:make-admin')
            ->assertSuccessful();
    }

    /** --admins narrows the table without misreporting the totals. */
    public function test_the_admins_filter_still_counts_everyone(): void
    {
        $this->makeUser('boss@escalate.test')->forceFill(['role' => 'admin'])->save();
        $this->makeUser('user1@escalate.test');
        $this->makeUser('user2@escalate.test');

        $this->artisan('escalate:users --admins')
            ->expectsOutputToContain('boss@escalate.test')
            ->doesntExpectOutputToContain('user1@escalate.test')
            ->expectsOutputToContain('1 admin, 3 accounts in total.')
            ->assertSuccessful();
    }

    public function test_granting_the_role_lets_that_account_through_the_door(): void
    {
        $user = $this->makeUser('promote@escalate.test');

        $this->assertFalse($user->isAdmin());

        $this->artisan('escalate:make-admin promote@escalate.test')->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());

        // And the role alone still only reaches the door, not past it.
        $this->actingAs($user->fresh())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($user->fresh())->get(route('admin.login'))->assertOk();
    }

    /** A near-miss email fails loudly rather than promoting somebody else. */
    public function test_an_email_that_does_not_exist_grants_nothing(): void
    {
        $this->makeUser('real@escalate.test');

        $this->artisan('escalate:make-admin you@example.com')->assertFailed();

        $this->assertSame(0, User::where('role', 'admin')->count());
    }
}
