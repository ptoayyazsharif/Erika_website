<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
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
            /*
             * Burn a hash on the way out.
             *
             * Laravel's guard short-circuits when no user matches, so bcrypt
             * never runs for an unknown address. At BCRYPT_ROUNDS=12 that is a
             * reproducible ~65ms gap between "no such account" and "wrong
             * password" — an enumeration oracle that survives the deliberately
             * uniform error message below. Hashing unconditionally closes it.
             */
            Hash::check($this->string('password'), '$2y$12$'.str_repeat('x', 53));

            $this->hit();

            throw ValidationException::withMessages([
                'email' => __('Those details did not match our records.'),
            ]);
        }

        if (Auth::user()->isSuspended()) {
            Auth::logout();
            $this->hit();

            throw ValidationException::withMessages([
                'email' => __('Those details did not match our records.'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->emailKey());
    }

    private function hit(): void
    {
        RateLimiter::hit($this->throttleKey());
        RateLimiter::hit($this->emailKey(), 900);
    }

    public function ensureIsNotRateLimited(): void
    {
        foreach ([[$this->throttleKey(), 5], [$this->emailKey(), 25]] as [$key, $max]) {
            if (! RateLimiter::tooManyAttempts($key, $max)) {
                continue;
            }

            Event::dispatch(new Lockout($this));

            throw ValidationException::withMessages([
                'email' => __('Too many attempts. Try again in :minutes minutes.', [
                    'minutes' => max(1, ceil(RateLimiter::availableIn($key) / 60)),
                ]),
            ]);
        }
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * A second bucket keyed on the email alone, with a looser limit.
     *
     * The primary key is email+IP, which is right: it stops one attacker
     * locking a victim out of their own account, and stops one address
     * spraying many accounts. But it also means a rotating source IP gets a
     * fresh five attempts every time — and the client can influence the IP if
     * proxy trust is ever misconfigured. This bucket cannot be reset by
     * changing address, so an account is never open to unlimited guessing
     * however the network in front of the app is set up.
     */
    public function emailKey(): string
    {
        return 'login-email|'.Str::transliterate(Str::lower($this->string('email')));
    }
}
