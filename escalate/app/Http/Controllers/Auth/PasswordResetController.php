<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * Forgotten passwords.
 *
 * Two things are load-bearing here.
 *
 * 1. **The response never reveals whether an account exists.** Laravel's
 *    default flow returns distinct statuses for "link sent" and "we can't find
 *    a user with that email address", and the usual Breeze controller surfaces
 *    both. On a private journal, membership is itself sensitive — someone
 *    checking whether their ex uses a manifestation app should learn nothing.
 *    So every outcome renders the same sentence, and the timing is dominated by
 *    the mail dispatch either way.
 *
 * 2. **Resetting a password does not lose the journal.** Entries are encrypted
 *    with APP_KEY, not with anything derived from the password, so a reset
 *    recovers the account intact. Users reasonably fear the opposite, so the
 *    screens say so.
 */
class PasswordResetController extends Controller
{
    /** The same answer whatever happened, so nothing can be inferred from it. */
    private const NEUTRAL = 'If that email has an account, a reset link is on its way. Check your spam folder too.';

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        // Deliberately ignoring the return value. Every branch — sent, throttled,
        // no such user — produces the identical message below.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', self::NEUTRAL);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            // Straight off the query string with no validation in front of it,
            // so an ?email[]=x turns Stringable into a 500 on a public route.
            'email' => scalar_input($request->query('email')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    // A new remember token invalidates every "keep me signed in"
                    // cookie. If the password was reset because someone else had
                    // it, leaving those alive would defeat the whole exercise.
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // An invalid or expired token is the one case worth naming, because
            // the user needs to know to request a new link. It reveals nothing:
            // anyone can generate an invalid token.
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'That reset link has expired or already been used. Ask for a new one.']);
        }

        // Sign every other session out. Laravel's logoutOtherDevices needs the
        // plaintext password, which we have here and nowhere else.
        Auth::logoutOtherDevices($request->string('password'));

        return redirect()->route('login')
            ->with('status', 'Your password is changed. Everything you had written is still there.');
    }
}
