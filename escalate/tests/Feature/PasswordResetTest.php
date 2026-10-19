<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_response_is_identical_whether_or_not_the_account_exists(): void
    {
        Notification::fake();

        $this->makeUser('real@escalate.test');

        $known = $this->post(route('password.email'), ['email' => 'real@escalate.test']);
        $unknown = $this->post(route('password.email'), ['email' => 'nobody@escalate.test']);

        // Membership of a private journal is itself sensitive — someone checking
        // whether their ex uses a manifestation app must learn nothing. Laravel's
        // default flow returns distinct statuses for these two cases.
        $this->assertSame(
            $known->getSession()->get('status'),
            $unknown->getSession()->get('status'),
        );
        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();

        // And only the real account is actually mailed.
        Notification::assertCount(1);
    }

    public function test_a_password_can_be_reset_and_the_journal_survives(): void
    {
        Notification::fake();

        $user = $this->makeUser('reset@escalate.test');
        $desire = $user->desires()->create(['title' => 'STILL-HERE-AFTERWARDS', 'status' => 'desired']);

        $this->post(route('password.email'), ['email' => 'reset@escalate.test']);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'reset@escalate.test',
            'password' => 'a-brand-new-password-9',
            'password_confirmation' => 'a-brand-new-password-9',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-brand-new-password-9', $user->fresh()->password));

        // Entries are encrypted with APP_KEY, not with anything derived from the
        // password, so a reset recovers the account intact. Users reasonably
        // fear the opposite; this proves it.
        $this->assertSame('STILL-HERE-AFTERWARDS', $desire->fresh()->title);

        $this->post(route('login.store'), [
            'email' => 'reset@escalate.test',
            'password' => 'a-brand-new-password-9',
        ])->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_a_reset_rotates_the_remember_token(): void
    {
        Notification::fake();

        $user = $this->makeUser('remember@escalate.test');
        $user->forceFill(['remember_token' => 'the-old-recaller'])->save();

        $this->post(route('password.email'), ['email' => 'remember@escalate.test']);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'remember@escalate.test',
            'password' => 'a-brand-new-password-9',
            'password_confirmation' => 'a-brand-new-password-9',
        ]);

        // If the password was reset because somebody else had it, leaving their
        // "keep me signed in" cookie alive would defeat the whole exercise.
        $this->assertNotSame('the-old-recaller', $user->fresh()->remember_token);
    }

    public function test_a_stale_or_forged_token_is_refused(): void
    {
        $this->makeUser('stale@escalate.test');

        $this->post(route('password.update'), [
            'token' => 'a-token-nobody-ever-issued',
            'email' => 'stale@escalate.test',
            'password' => 'a-brand-new-password-9',
            'password_confirmation' => 'a-brand-new-password-9',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.store'), [
            'email' => 'stale@escalate.test',
            'password' => 'a-brand-new-password-9',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_reset_still_enforces_the_password_rules(): void
    {
        Notification::fake();

        $user = $this->makeUser('weak@escalate.test');
        $this->post(route('password.email'), ['email' => 'weak@escalate.test']);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        // The 12-character floor exists because this account holds a private
        // journal; a reset must not be a way around it.
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'weak@escalate.test',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ])->assertSessionHasErrors('password');
    }

    public function test_the_faith_language_is_encrypted_at_rest(): void
    {
        $user = $this->makeUser('belief@escalate.test');
        $user->world()->update(['faith_language' => 'god']);

        $raw = \DB::table('profiles')->where('user_id', $user->id)->value('faith_language');

        // Religious belief is special-category data. "none" is equally
        // revealing, so no value of this field is safe in the clear.
        $this->assertStringNotContainsString('god', (string) $raw);
        $this->assertSame('god', $user->fresh()->profile->faith_language);
    }

    public function test_a_new_profile_still_defaults_to_secular(): void
    {
        $user = $this->makeUser('default@escalate.test');

        // Encrypting the column meant dropping its DEFAULT 'none', which cannot
        // be expressed as ciphertext. The default moved to the model.
        $this->assertSame('none', $user->profile->faith_language);
    }
}
