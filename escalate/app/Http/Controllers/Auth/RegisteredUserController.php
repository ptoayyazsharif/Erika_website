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
        ]);

        // Mass assignment can't set `role`: it is absent from User::$fillable,
        // so a posted role field is silently dropped rather than honoured.
        $user = User::create($data);

        $user->profile()->create();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('world.edit')
            ->with('status', 'Welcome. Let’s set up your world.');
    }
}
