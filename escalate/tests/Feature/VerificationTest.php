<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Confirming an email address.
 *
 * Two things are being asserted, and the second matters as much as the first:
 * that an unconfirmed account cannot spend money, and that it is not otherwise
 * locked out. Gating the whole app on a confirmation email would mean a spam
 * filter can take somebody's journal away from them.
 */
class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('escalate.beta.require_verification', true);
        Config::set('escalate.beta.invite_only', false);
    }

    private function unverified(string $email = 'unverified@escalate.test'): User
    {
        $user = $this->makeUser($email);
        $user->forceFill(['email_verified_at' => null])->save();

        return $user->fresh();
    }

    public function test_registering_sends_a_verification_email(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name'                  => 'New Person',
            'email'                 => 'new@escalate.test',
            'password'              => 'a-long-enough-password-1',
            'password_confirmation' => 'a-long-enough-password-1',
            'agree'                 => '1',
            'age'                   => '1',
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo(User::firstWhere('email', 'new@escalate.test'), VerifyEmail::class);
    }

    /** The four routes that call a paid provider, and only those. */
    public function test_an_unverified_account_cannot_spend_anything(): void
    {
        $user = $this->unverified();
        $desire = $user->desires()->create(['title' => 'A quiet house']);
        $story = $this->makeReadyStory($user, null, $desire->id);

        $notice = route('verification.notice');

        $this->actingAs($user)->post(route('stories.store', $desire))->assertRedirect($notice);
        $this->actingAs($user)->post(route('stories.narrate', $story))->assertRedirect($notice);
        $this->actingAs($user)->post(route('stories.regenerate', $story))->assertRedirect($notice);

        $this->assertSame(0, $user->stories()->where('state', 'queued')->count());
    }

    public function test_an_unverified_account_can_still_use_the_rest_of_the_app(): void
    {
        $user = $this->unverified();

        foreach (['today', 'desires.index', 'desires.create', 'world.edit', 'gratitude.index', 'journey', 'account.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }

        // And still write things down — naming a desire costs nothing.
        $this->actingAs($user)->post(route('desires.store'), [
            'title'        => 'A quiet house',
            'category'     => 'home',
            'timeframe'    => 'this_year',
            'story_length' => 'short',
            'perspective'  => 'first',
            'tone'         => 'grounded',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $user->desires()->count());
    }

    public function test_following_the_link_confirms_the_address_and_opens_generation(): void
    {
        $user = $this->unverified();

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id'   => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($link)->assertRedirect(route('today'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $desire = $user->desires()->create(['title' => 'A quiet house']);
        $this->actingAs($user->fresh())->post(route('stories.store', $desire))->assertRedirect();
        $this->assertSame(1, $user->stories()->count());
    }

    /** An unsigned or tampered link is worthless. */
    public function test_an_unsigned_link_does_not_verify(): void
    {
        $user = $this->unverified();

        $this->actingAs($user)
            ->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /**
     * A signed link for someone else's account must not work even when the
     * signature is genuine — the id in the URL is not the proof of anything.
     */
    public function test_a_link_cannot_verify_a_different_account(): void
    {
        $victim = $this->unverified('victim@escalate.test');
        $attacker = $this->unverified('attacker@escalate.test');

        $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id'   => $victim->id,
            'hash' => sha1($victim->email),
        ]);

        $this->actingAs($attacker)->get($link)->assertForbidden();

        $this->assertFalse($victim->fresh()->hasVerifiedEmail());
    }

    public function test_the_gate_can_be_turned_off_for_an_install_without_mail(): void
    {
        Config::set('escalate.beta.require_verification', false);

        $user = $this->unverified();
        $desire = $user->desires()->create(['title' => 'A quiet house']);

        $this->actingAs($user)->post(route('stories.store', $desire))->assertRedirect();
        $this->assertSame(1, $user->stories()->count());
    }
}
