<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The admin door, now that it is optional, and the way in.
 *
 * AdminPanelTest still covers the door working when it is switched on. This
 * covers the default — off — and the one property that must survive the switch
 * in either position: an ordinary user must not be able to tell the admin area
 * exists.
 */
class AdminDoorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'boss@escalate.test'): User
    {
        $user = $this->makeUser($email, 'Boss');
        $user->forceFill(['role' => 'admin'])->save();

        return $user->fresh();
    }

    /** The report that prompted this: a password screen in front of someone who never signed out. */
    public function test_by_default_an_admin_walks_straight_in(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Overview');
    }

    /**
     * A bookmark to the door should not be a dead end once the door is gone.
     * Nor should posting to it from a stale tab.
     */
    public function test_the_door_itself_just_lets_them_through_when_it_is_off(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->post(route('admin.login.store'), ['password' => 'wrong-on-purpose'])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHasNoErrors();
    }

    /**
     * The property the whole 404-not-403 design protects, asserted in both
     * positions of the switch. If turning the door off ever made the admin
     * area detectable, that would be a far worse regression than the
     * inconvenience the switch exists to remove.
     */
    public function test_an_ordinary_user_cannot_tell_the_admin_area_exists_either_way(): void
    {
        foreach ([false, true] as $doorOn) {
            Config::set('escalate.admin.confirm_password', $doorOn);

            $user = $this->makeUser('curious'.(int) $doorOn.'@escalate.test');

            $this->actingAs($user)->get(route('admin.dashboard'))->assertNotFound();
            $this->actingAs($user)->get(route('admin.login'))->assertNotFound();
            $this->actingAs($user)
                ->post(route('admin.login.store'), ['password' => 'a-long-enough-password-1'])
                ->assertNotFound();
        }
    }

    /* ── the way in ──────────────────────────────────────────────────────── */

    public function test_an_admin_is_shown_a_link_to_the_admin_panel(): void
    {
        $this->actingAs($this->admin())
            ->get(route('today'))
            ->assertOk()
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }

    /**
     * And an ordinary user is not. A link in the topbar would announce the
     * admin area to everybody and undo the 404 above — which is the sort of
     * leak that arrives by way of a convenience.
     */
    public function test_an_ordinary_user_is_shown_no_such_link(): void
    {
        $this->actingAs($this->makeUser('member@escalate.test'))
            ->get(route('today'))
            ->assertOk()
            ->assertDontSee(route('admin.dashboard'), false)
            ->assertDontSee('>Admin<', false);
    }

    /* ── the install nudge ───────────────────────────────────────────────── */

    /**
     * It ships hidden and app.js decides. Rendering it visible would put a
     * "add me to your home screen" banner in front of somebody who already did.
     */
    public function test_the_install_tip_ships_hidden(): void
    {
        $html = $this->actingAs($this->makeUser('tip@escalate.test'))
            ->get(route('today'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div class="install-tip"[^>]*\shidden/', $html);
    }
}
