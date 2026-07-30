@extends('layouts.auth', ['title' => 'Set a new password'])

@section('heading', 'Set a new password.')
@section('sub', 'Twelve characters or more, with letters and numbers. Signing in again afterwards will show everything exactly as you left it.')

@section('form')
<form method="POST" action="{{ route('password.update') }}" class="card" data-once>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="field">
        <label for="email">Your email</label>
        <input class="input" id="email" name="email" type="email" inputmode="email"
               autocomplete="username" required value="{{ old('email', $email) }}"
               @error('email') aria-invalid="true" @enderror>
    </div>

    <div class="field">
        <label for="password">New password</label>
        <input class="input" id="password" name="password" type="password"
               autocomplete="new-password" required autofocus
               @error('password') aria-invalid="true" @enderror>
    </div>

    <div class="field">
        <label for="password_confirmation">New password again</label>
        <input class="input" id="password_confirmation" name="password_confirmation"
               type="password" autocomplete="new-password" required>
    </div>

    <p class="small muted" style="margin-bottom:var(--s-5)">
        This signs you out everywhere else, in case someone other than you asked
        for this link.
    </p>

    <button class="btn btn-full" type="submit" data-busy="Saving…">Set my password</button>
</form>
@endsection
