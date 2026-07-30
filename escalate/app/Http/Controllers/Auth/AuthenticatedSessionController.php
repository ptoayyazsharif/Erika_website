<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Fresh session id, so a session fixated before login is worthless.
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        // A new user goes to My World first — the generator is only as good as
        // what it knows, and an empty profile produces generic stories.
        $target = $user->world()->onboarded ? route('today') : route('world.edit');

        return redirect()->intended($target);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        // Both matter: invalidate() drops the session data, regenerateToken()
        // stops the old CSRF token being replayed against the next session.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        /*
        | Clear-Site-Data tells the browser to drop its HTTP cache and its
        | storage for this origin as part of the sign-out response. That covers
        | the service worker's Cache Storage deterministically, which a
        | postMessage on an unloading page cannot promise, and it clears any
        | HTML the browser held before the no-store header was in place.
        |
        | "executionContexts" is deliberately omitted: it forces a reload of the
        | page we are redirecting to, which fights the redirect. Cookies are
        | already gone via invalidate().
        */
        return redirect()->route('login')
            ->with('status', 'You are signed out.')
            ->header('Clear-Site-Data', '"cache", "storage"');
    }
}
