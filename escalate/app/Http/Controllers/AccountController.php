<?php

namespace App\Http\Controllers;

use App\Services\AccountEraser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    private function confirmPassword(Request $request): void
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->string('password'), $request->user()->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => 'That password is not right.',
            ]);
        }
    }
}
