<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
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
            'invite' => Invite::normalise((string) $request->query('invite', '')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
        unset($data['agree'], $data['age']);
        $invite = $this->resolveInvite($data['email'], $data['invite'] ?? null);
        unset($data['invite']);

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
     * The invite this signup will spend, or null when the beta is open.
     *
     * Every failure gets the same sentence. Distinguishing "no such code" from
     * "already used" from "that code is not for this address" would turn the
     * register form into an oracle for who else has been invited — and on an
     * app whose entire premise is privacy, the guest list is not public.
     */
    private function resolveInvite(string $email, ?string $code): ?Invite
    {
        if (! $this->inviteRequired()) {
            return null;
        }

        $invite = Invite::where('code', Invite::normalise((string) $code))->first();

        if (! $invite || ! $invite->isUsable($email)) {
            throw ValidationException::withMessages([
                'invite' => 'That invite code is not valid. Check it against the one you were sent.',
            ]);
        }

        return $invite;
    }
}
