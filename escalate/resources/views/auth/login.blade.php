@extends('layouts.auth', ['title' => 'Sign in'])

@section('heading', 'Welcome back.')
@section('sub', 'Your stories are where you left them.')

@section('form')
<form method="POST" action="{{ route('login.store') }}" class="card" data-once>
    @csrf

    <div class="field">
        <label for="email">Email</label>
        <input class="input" id="email" name="email" type="email" inputmode="email"
               autocomplete="username" required autofocus
               value="{{ old('email') }}"
               @error('email') aria-invalid="true" @enderror>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input class="input" id="password" name="password" type="password"
               autocomplete="current-password" required
               @error('password') aria-invalid="true" @enderror>
    </div>

    <label class="option" style="margin-bottom:var(--s-5)">
        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
        <span class="tick" aria-hidden="true"></span>
        <span class="option-body">
            <span class="option-label">Keep me signed in</span>
            <small>Only on a device that is yours alone.</small>
        </span>
    </label>

    <button class="btn btn-full" type="submit" data-busy="Signing in…">Sign in</button>
</form>

<p class="small muted center" style="margin-top:var(--s-5)">
    No account yet? <a href="{{ route('register') }}">Create one</a>
</p>
@endsection
