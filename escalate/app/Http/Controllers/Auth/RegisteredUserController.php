<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ApplicationController as AdminApplications;
use App\Models\Invite;
use App\Models\User;
use App\Support\Plan;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.register', [
            // Prefilled from ?invite=… so the link in the email is one click
            // and nobody has to retype twelve characters on a phone.
            'invite' => Invite::normalise(scalar_input($request->query('invite'))),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /*
         * The invite is checked BEFORE anything else, and the ordering is a
         * security property rather than tidiness.
         *
         * Validating everything in one call means Laravel evaluates every rule
         * and returns every failure together — including `unique:users,email`,
         * whose message is "The email has already been taken." So a stranger
         * with no invite code at all could post an address and read, from the
         * presence or absence of that sentence, whether it has an account here.
         *
         * That is precisely the thing LoginRequest and PasswordResetController
         * go out of their way to prevent, and for the same reason: on a private
         * journal, membership is itself sensitive. Someone checking whether
         * their ex uses a manifestation app should learn nothing.
         *
         * Gating first means an invalid code ends the request before the email
         * is ever looked at, so there is nothing to read. Anyone still able to
         * probe is holding a valid, unclaimed invite — a person we deliberately
         * let in, and whose code is on record.
         */
        $invite = $this->gate($request);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:80'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            // 12 characters minimum. This account holds a private journal, and
            // the whole security model rests on the password — so it is longer
            // than Laravel's default eight.
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],

            // Both are 'accepted', so an absent or false value fails. Recorded
            // rather than assumed: this app sends what people write to two AI
            // companies, and that is not something to infer from silence.
            'agree'  => ['accepted'],
            'age'    => ['accepted'],

            // Present in the rules whether or not the beta is closed, so the
            // field always survives a failed submission and nobody has to find
            // their code again after mistyping a password.
            'invite' => [$this->inviteRequired() ? 'required' : 'nullable', 'string', 'max:32'],
        ], [
            'agree.accepted' => 'Please confirm you have read what happens to what you write.',
            'age.accepted'   => 'Escalate is for people aged 16 and over.',
            'invite.required' => 'Escalate is invite-only right now. Enter the code you were sent.',
        ]);

        // Not part of the model's fillable data — strip before create().
        unset($data['agree'], $data['age'], $data['invite']);

        // Only now, with a real address in hand, is the binding checked. A
        // bound invite must not open for anyone but its own recipient.
        if ($invite && ! $invite->matchesEmail($data['email'])) {
            throw ValidationException::withMessages(['invite' => self::REFUSED]);
        }

        /*
         * One transaction, and the claim goes first.
         *
         * Claiming before creating means the invite's own uniqueness check —
         * the conditional UPDATE in Invite::claim() — is what decides who gets
         * in when two people race the same code. Creating the user first and
         * claiming afterwards would leave the loser of that race holding a real
         * account, which is the exact thing an invite is supposed to prevent.
         *
         * If anything below throws, the claim rolls back with it and the code
         * is good again.
         */
        $user = DB::transaction(function () use ($data, $invite) {
            // Mass assignment can't set `role`: it is absent from User::$fillable,
            // so a posted role field is silently dropped rather than honoured.
            $user = User::create($data);

            if ($invite && ! $invite->claim($user)) {
                // Somebody else spent it between the check above and here.
                throw ValidationException::withMessages([
                    'invite' => 'That invite has just been used. Ask for another.',
                ]);
            }

            /*
             * The cohort, and what was promised with it.
             *
             * An invite minted by Admin → Applications carries "Founding 25" in
             * its note. Copying it onto the user here means the promise outlives
             * the invite row, and the comp is applied at the same moment rather
             * than being something somebody has to remember to do afterwards —
             * a founding tester who gets billed because a step was missed is
             * the one mistake this cohort must not experience.
             *
             * plan_override rather than a Stripe coupon: Plan::for() checks it
             * before Stripe, it costs no billing code, it is visible on the
             * admin People screen, and they never meet a card form at all.
             */
            if ($invite && $invite->note === AdminApplications::COHORT) {
                $user->forceFill([
                    'cohort'        => AdminApplications::COHORT,
                    'plan_override' => Plan::paidKey(),
                ])->save();
            }

            // Timestamped, so the consent can be evidenced later rather than
            // asserted. Stored on the profile because it belongs with the person,
            // not with the session that happened to create them.
            $user->profile()->create(['consented_at' => now()]);

            return $user;
        });

        // Sends the verification email, via the framework's own listener.
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('world.edit')
            ->with('status', 'Welcome. Let’s set up your world.');
    }

    private function inviteRequired(): bool
    {
        return (bool) config('escalate.beta.invite_only');
    }

    /**
     * Every invite failure gets this one sentence.
     *
     * Distinguishing "no such code" from "already used" from "not for this
     * address" would turn the register form into an oracle for who else has
     * been invited — and on an app whose entire premise is privacy, the guest
     * list is not public either.
     */
    private const REFUSED = 'That invite code is not valid. Check it against the one you were sent.';

    /**
     * The invite this signup will spend, or null when the beta is open.
     *
     * Validates the code ON ITS OWN, before any other field is examined. The
     * email binding cannot be checked here — there is no validated email yet —
     * so store() re-checks it once there is one. That split is the price of not
     * leaking, and it is worth paying.
     */
    private function gate(Request $request): ?Invite
    {
        if (! $this->inviteRequired()) {
            return null;
        }

        $request->validate(
            ['invite' => ['required', 'string', 'max:32']],
            ['invite.required' => 'Escalate is invite-only right now. Enter the code you were sent.'],
        );

        $invite = Invite::where('code', Invite::normalise(scalar_input($request->input('invite'))))->first();

        // Deliberately not isUsable($email): the address is unvalidated at this
        // point, and feeding it to a lookup here is how the leak would creep
        // back in.
        if (! $invite || $invite->isClaimed() || $invite->isExpired()) {
            throw ValidationException::withMessages(['invite' => self::REFUSED]);
        }

        return $invite;
    }
}
