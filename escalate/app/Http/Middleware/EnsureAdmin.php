<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin gate.
 *
 * The role check is unconditional. The second door — a session flag proving
 * they came through /admin/login rather than the ordinary one — is behind
 * `escalate.admin.confirm_password`, and is off by default.
 *
 * What that flag buys when it is on: a stolen or borrowed ordinary session
 * cannot reach the admin area, because an admin browsing the app as a user
 * holds no admin flag until they deliberately re-authenticate. With it off, an
 * unlocked device is an unlocked admin panel. That is a real trade, made
 * knowingly, and reversible from Settings without a deploy.
 *
 * A failed role check 404s rather than 403s. There is no reason to confirm to
 * a prober that /admin exists.
 *
 * Missing the session flag is different, and used to 404 too. That was wrong:
 * it is the state every admin is in when they first arrive, so the only door
 * into the admin area was a URL nobody is ever shown. An admin now gets sent
 * to it. Nothing leaks — you have to already hold an admin account to tell
 * this redirect apart from the 404 above, and holding one is exactly what the
 * 404 exists to hide.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(404);
        }

        // The second door is optional, and off by default. Somebody who is
        // already signed in reads "Confirm it's you" as having been signed out,
        // which is a poor greeting for the two people who use this daily. The
        // role check above is unconditional and does not move.
        if (! config('escalate.admin.confirm_password')) {
            return $next($request);
        }

        // guest() rather than route(): it records where they were heading, so
        // a link straight to Settings still lands on Settings once the
        // password is confirmed, instead of dumping everyone on the dashboard.
        if (! $request->session()->get('admin.verified')) {
            return redirect()->guest(route('admin.login'));
        }

        // Admins re-authenticate every two hours of admin-area idleness.
        $last = (int) $request->session()->get('admin.verified_at', 0);

        if ($last < now()->subHours(2)->timestamp) {
            $request->session()->forget(['admin.verified', 'admin.verified_at']);

            return redirect()->guest(route('admin.login'))
                ->with('status', 'Confirm your password to continue.');
        }

        $request->session()->put('admin.verified_at', now()->timestamp);

        return $next($request);
    }
}
