<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login attempt.
 *
 * Three things worth noting, all of them security-relevant:
 *
 *  1. The failure message never distinguishes "no such account" from "wrong
 *     password". Either one would let someone enumerate who has an account
 *     here, and for a private journal the mere fact of membership is sensitive.
 *
 *  2. Throttling is keyed on email *and* IP together, so one attacker cannot
 *     lock a victim out of their own account by failing logins on their behalf,
 *     and cannot spread an attack across many accounts from one address.
 *
 *  3. A suspended account fails with the same generic message. An admin
 *     suspending someone should not thereby confirm the account exists.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('Those details did not match our records.'),
            ]);
        }

        if (Auth::user()->isSuspended()) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('Those details did not match our records.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        Event::dispatch(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many attempts. Try again in :minutes minutes.', [
                'minutes' => max(1, ceil($seconds / 60)),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
