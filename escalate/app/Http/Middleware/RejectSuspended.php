<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspension has to be enforced on every request, not just at login.
 *
 * LoginRequest checks isSuspended() when someone signs in, which sounds
 * sufficient and is not. An account suspended while it is already signed in
 * keeps full access until its session lapses — and with "Keep me signed in"
 * the recaller cookie re-authenticates it without ever re-entering
 * LoginRequest, so the check is never reached again. An admin suspending an
 * abusive account would watch it carry on writing and spending AI quota.
 */
class RejectSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // This middleware intercepts POST /logout too, so the ordinary
            // sign-out response never runs for a suspended account. Without
            // this header the one browser you most want cleared is the one
            // that keeps its cache, storage and worker registration.
            return redirect()->route('login')
                ->with('status', 'This account is not available. Get in touch if you think that is a mistake.')
                ->header('Clear-Site-Data', '"cache", "storage"');
        }

        return $next($request);
    }
}
