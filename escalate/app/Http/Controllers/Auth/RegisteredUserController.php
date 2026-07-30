<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
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
        ], [
            'agree.accepted' => 'Please confirm you have read what happens to what you write.',
            'age.accepted'   => 'Escalate is for people aged 16 and over.',
        ]);

        // Not part of the model's fillable data — strip before create().
        unset($data['agree'], $data['age']);

        // Mass assignment can't set `role`: it is absent from User::$fillable,
        // so a posted role field is silently dropped rather than honoured.
        $user = User::create($data);

        // Timestamped, so the consent can be evidenced later rather than
        // asserted. Stored on the profile because it belongs with the person,
        // not with the session that happened to create them.
        $user->profile()->create(['consented_at' => now()]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('world.edit')
            ->with('status', 'Welcome. Let’s set up your world.');
    }
}
