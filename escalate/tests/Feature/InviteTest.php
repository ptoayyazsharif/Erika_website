<?php

namespace Tests\Feature;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The door to the closed beta.
 *
 * The thing being protected is not the app's secrecy — it is the provider
 * bill. Every account that exists can spend five readings and eight narrations
 * a day on someone else's API, so "who may create an account" is a spending
 * control before it is anything else.
 */
class InviteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('escalate.beta.invite_only', true);
    }

    private function signup(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Beta Tester',
            'email'                 => 'beta@escalate.test',
            'password'              => 'a-long-enough-password-1',
            'password_confirmation' => 'a-long-enough-password-1',
            'agree'                 => '1',
            'age'                   => '1',
        ], $overrides);
    }

    public function test_registration_is_refused_without_an_invite(): void
    {
        $this->post(route('register.store'), $this->signup())
            ->assertSessionHasErrors('invite');

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_a_valid_invite_lets_someone_in_and_is_spent(): void
    {
        $invite = Invite::mint();

        $this->post(route('register.store'), $this->signup(['invite' => $invite->code]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('world.edit'));

        $user = User::firstWhere('email', 'beta@escalate.test');

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $invite->refresh();
        $this->assertTrue($invite->isClaimed());
        $this->assertSame($user->id, $invite->claimed_by);
        $this->assertFalse($invite->isUsable());
    }

    /** One code, one account — the whole point of the thing. */
    public function test_an_invite_cannot_be_used_twice(): void
    {
        $invite = Invite::mint();

        $this->post(route('register.store'), $this->signup(['invite' => $invite->code]))
            ->assertSessionHasNoErrors();

        // register.store sits behind 'guest', and the first signup left this
        // session authenticated — so a second POST would be redirected away
        // rather than reaching the invite check at all.
        $this->signOut();

        $this->post(route('register.store'), $this->signup([
            'invite' => $invite->code,
            'email'  => 'second@escalate.test',
        ]))->assertSessionHasErrors('invite');

        $this->assertNull(User::firstWhere('email', 'second@escalate.test'));
        $this->assertSame(1, User::count());
    }

    /**
     * A failed signup must not burn the code.
     *
     * Mistyping a password confirmation is the most ordinary thing in the
     * world, and spending someone's only invite on it would end their beta
     * before it started.
     */
    public function test_a_rejected_signup_leaves_the_invite_unspent(): void
    {
        $invite = Invite::mint();

        $this->post(route('register.store'), $this->signup([
            'invite'                => $invite->code,
            'password_confirmation' => 'something-else-entirely-2',
        ]))->assertSessionHasErrors('password');

        $this->assertFalse($invite->refresh()->isClaimed());
        $this->assertSame(0, User::count());
    }

    public function test_an_expired_invite_is_refused(): void
    {
        $invite = Invite::mint(null, null, 30);
        $invite->forceFill(['expires_at' => now()->subDay()])->save();

        $this->post(route('register.store'), $this->signup(['invite' => $invite->code]))
            ->assertSessionHasErrors('invite');

        $this->assertSame(0, User::count());
    }

    /** A forwarded code is useless when the invite names an address. */
    public function test_a_bound_invite_only_works_for_its_own_address(): void
    {
        $invite = Invite::mint('maya@escalate.test');

        $this->post(route('register.store'), $this->signup([
            'invite' => $invite->code,
            'email'  => 'someone.else@escalate.test',
        ]))->assertSessionHasErrors('invite');

        $this->assertFalse($invite->refresh()->isClaimed());

        $this->post(route('register.store'), $this->signup([
            'invite' => $invite->code,
            'email'  => 'maya@escalate.test',
        ]))->assertSessionHasNoErrors();

        $this->assertTrue($invite->refresh()->isClaimed());
    }

    /**
     * Codes get read down a phone and copied out of texts. Lower case, missing
     * dashes and stray spaces all have to work, or the person concludes they
     * were sent something broken.
     */
    public function test_a_code_is_accepted_however_it_was_retyped(): void
    {
        $invite = Invite::mint();
        $mangled = ' '.strtolower(str_replace('-', '', $invite->code)).' ';

        $this->post(route('register.store'), $this->signup(['invite' => $mangled]))
            ->assertSessionHasNoErrors();

        $this->assertTrue($invite->refresh()->isClaimed());
    }

    /** Every rejection reads the same, so the form is not a guest list oracle. */
    public function test_no_rejection_reveals_whether_a_code_exists(): void
    {
        $claimed = Invite::mint();
        $claimed->claim($this->makeUser('holder@escalate.test'));

        $expired = Invite::mint();
        $expired->forceFill(['expires_at' => now()->subDay()])->save();

        $bound = Invite::mint('someone@escalate.test');

        // Spent, expired, bound to another address, and never issued at all.
        // A different sentence for any of these would turn the register form
        // into a way to find out who else has been invited.
        $sentence = 'That invite code is not valid. Check it against the one you were sent.';

        foreach ([$claimed->code, $expired->code, $bound->code, 'ZZZZ-ZZZZ-ZZZZ'] as $i => $code) {
            $this->signOut();

            $this->post(route('register.store'), $this->signup([
                'invite' => $code,
                'email'  => "probe{$i}@escalate.test",
            ]))->assertSessionHasErrors(['invite' => $sentence]);
        }
    }

    public function test_signup_is_open_when_the_beta_gate_is_off(): void
    {
        Config::set('escalate.beta.invite_only', false);

        $this->post(route('register.store'), $this->signup())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('world.edit'));

        $this->assertSame(1, User::count());
    }

    /**
     * Registration must not report on an address to someone without a code.
     *
     * Validating every rule in one call returns every failure together, so
     * `unique:users,email` — "The email has already been taken." — was
     * answering "does this person have an account here" for any stranger who
     * asked. Login and password reset both go to real lengths to avoid exactly
     * that; this was the door left open beside them.
     */
    public function test_registration_does_not_reveal_whether_an_address_has_an_account(): void
    {
        $this->makeUser('known@escalate.test');

        foreach (['known@escalate.test', 'unknown@escalate.test'] as $email) {
            $this->signOut();

            $this->post(route('register.store'), $this->signup([
                'email'  => $email,
                'invite' => '',
            ]))
                ->assertSessionHasErrors('invite')
                // The tell. An `email` error appears only for an address that
                // already has an account, so its presence is the answer.
                ->assertSessionDoesntHaveErrors('email');
        }
    }

    /** The same, with a code that is simply wrong rather than absent. */
    public function test_a_bad_code_reveals_nothing_about_the_address_either(): void
    {
        $this->makeUser('known2@escalate.test');

        foreach (['known2@escalate.test', 'unknown2@escalate.test'] as $email) {
            $this->signOut();

            $this->post(route('register.store'), $this->signup([
                'email'  => $email,
                'invite' => 'ZZZZ-ZZZZ-ZZZZ',
            ]))
                ->assertSessionHasErrors('invite')
                ->assertSessionDoesntHaveErrors('email');
        }
    }

    /** A duplicate address is still refused — to someone holding a real code. */
    public function test_a_duplicate_address_is_still_refused_with_a_valid_invite(): void
    {
        $this->makeUser('taken@escalate.test');
        $invite = Invite::mint();

        $this->post(route('register.store'), $this->signup([
            'email'  => 'taken@escalate.test',
            'invite' => $invite->code,
        ]))->assertSessionHasErrors('email');

        // And the code is not spent on the attempt.
        $this->assertFalse($invite->refresh()->isClaimed());
    }

    public function test_the_form_prefills_a_code_from_the_invite_link(): void
    {
        $invite = Invite::mint();

        $this->get($invite->url())
            ->assertOk()
            ->assertSee('value="'.$invite->code.'"', false);
    }
}
