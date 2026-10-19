<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The admin door.
 *
 * This is a second authentication, not a second account. An admin signs into
 * the app like anyone else, and then confirms their password again here to put
 * an `admin.verified` flag on the session. EnsureAdmin requires that flag and
 * expires it after two hours.
 *
 * All of that is behind `escalate.admin.confirm_password`, which is off by
 * default. With it off this controller is a pass-through: both actions send an
 * admin on to the dashboard rather than 404ing a bookmark or showing a form
 * that confirms nothing.
 *
 * Why bother, when the role check alone would "work": it shrinks the window in
 * which a stolen or borrowed session can reach the admin area to the length of
 * one deliberate re-authentication. An admin who leaves a laptop open on the
 * Today screen has not left the admin panel open.
 *
 * Throttling here is stricter than ordinary login — three attempts, keyed on
 * the user id, because we already know exactly who is knocking.
 */
class AdminSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        // Never confirm that an admin area exists to someone who is not an
        // admin. A stranger and an ordinary user both get the same 404. This
        // check comes first and is not affected by the setting below.
        if (! $request->user()?->isAdmin()) {
            abort(404);
        }

        // With the door switched off there is nothing to confirm, and showing
        // the form anyway would be the exact thing that was reported: a
        // password screen in front of somebody who never signed out. A
        // bookmark to this URL should just go in.
        if (! config('escalate.admin.confirm_password')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
            abort(404);
        }

        // Posting to a door that is switched off is not an error worth showing
        // anybody — it is a stale form or a stale tab. Let them in.
        if (! config('escalate.admin.confirm_password')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        $key = 'admin-login|'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'password' => __('Too many attempts. Try again in :minutes minutes.', [
                    'minutes' => max(1, ceil(RateLimiter::availableIn($key) / 60)),
                ]),
            ]);
        }

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->string('password'), $user->password)) {
            RateLimiter::hit($key, 900);

            throw ValidationException::withMessages([
                'password' => __('That password is not right.'),
            ]);
        }

        RateLimiter::clear($key);

        // Rotate the session id on privilege escalation, exactly as on login.
        $request->session()->regenerate();
        $request->session()->put('admin.verified', true);
        $request->session()->put('admin.verified_at', now()->timestamp);

        return redirect()->intended(route('admin.dashboard'));
    }

    /** Leave the admin area without signing out of the app. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin.verified', 'admin.verified_at']);

        return redirect()->route('today')->with('status', 'Left the admin area.');
    }
}
