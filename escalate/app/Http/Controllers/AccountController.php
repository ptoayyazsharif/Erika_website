<?php

namespace App\Http\Controllers;

use App\Services\AccountEraser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

/**
 * Leaving.
 *
 * A journal that cannot be taken away or deleted is not really the user's. Both
 * actions require the password again — not as theatre, but because a borrowed
 * unlocked phone should not be able to download somebody's whole journal or
 * destroy it.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.index');
    }

    /** Everything we hold, as a JSON file. */
    public function export(Request $request, AccountEraser $eraser): StreamedResponse
    {
        $this->confirmPassword($request);

        $data = $eraser->export($request->user());
        $name = 'escalate-export-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $name, [
            'Content-Type'  => 'application/json',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Delete the account and everything in it. */
    public function destroy(Request $request, AccountEraser $eraser): RedirectResponse
    {
        $this->confirmPassword($request);

        $request->validate([
            // Typing the word is the last guard against a mis-click on an
            // irreversible action.
            'confirm' => ['required', 'string', 'in:DELETE'],
        ], [
            'confirm.in' => 'Type DELETE to confirm.',
        ]);

        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $eraser->erase($user);

        return redirect()->route('login')
            ->with('status', 'Your account and everything in it has been deleted.')
            ->header('Clear-Site-Data', '"cache", "storage"');
    }

    /**
     * Re-confirm the password, with a lockout that does not depend on the IP.
     *
     * Without this the export route is a password oracle that pays out the
     * entire decrypted journal on a correct guess, metered only by a route
     * throttle keyed on the client address — which is exactly the thing an
     * attacker can vary. Five attempts per user per fifteen minutes, keyed on
     * the user id, cannot be reset by changing network.
     */
    private function confirmPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);

        $key = 'account-confirm|'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'password' => __('Too many attempts. Try again in :minutes minutes.', [
                    'minutes' => max(1, ceil(RateLimiter::availableIn($key) / 60)),
                ]),
            ]);
        }

        if (! Hash::check($request->string('password'), $request->user()->password)) {
            RateLimiter::hit($key, 900);

            throw ValidationException::withMessages([
                'password' => 'That password is not right.',
            ]);
        }

        RateLimiter::clear($key);
    }
}
