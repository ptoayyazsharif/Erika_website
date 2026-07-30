@extends('layouts.auth', ['title' => 'Forgotten password'])

@section('heading', 'Let’s get you back in.')
@section('sub', 'We’ll email you a link to set a new password. Everything you have written stays exactly where it is — your journal is not encrypted with your password.')

@section('form')
<form method="POST" action="{{ route('password.email') }}" class="card" data-once>
    @csrf

    <div class="field">
        <label for="email">Your email</label>
        <input class="input" id="email" name="email" type="email" inputmode="email"
               autocomplete="username" required autofocus value="{{ old('email') }}"
               @error('email') aria-invalid="true" @enderror>
    </div>

    <button class="btn btn-full" type="submit" data-busy="Sending…">Send the link</button>
</form>

<p class="small muted center" style="margin-top:var(--s-5)">
    <a href="{{ route('login') }}">Back to sign in</a>
</p>
@endsection
