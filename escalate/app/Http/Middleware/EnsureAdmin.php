<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin gate.
 *
 * Two conditions, both required: an authenticated user with the admin role,
 * and a session flag proving they came through /admin/login rather than the
 * ordinary one. The second condition is what stops a stolen ordinary session
 * from reaching the admin area — an admin browsing the app as a user has no
 * admin session flag until they log in again on the admin form.
 *
 * A failed check 404s rather than 403s. There is no reason to confirm to a
 * prober that /admin exists.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin() || ! $request->session()->get('admin.verified')) {
            abort(404);
        }

        // Admins re-authenticate every two hours of admin-area idleness.
        $last = (int) $request->session()->get('admin.verified_at', 0);

        if ($last < now()->subHours(2)->timestamp) {
            $request->session()->forget(['admin.verified', 'admin.verified_at']);

            return redirect()->route('admin.login')
                ->with('status', 'Confirm your password to continue.');
        }

        $request->session()->put('admin.verified_at', now()->timestamp);

        return $next($request);
    }
}
