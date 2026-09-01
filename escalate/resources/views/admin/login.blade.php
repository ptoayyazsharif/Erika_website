@extends('layouts.auth', ['title' => 'Admin'])

@section('heading', 'Confirm it’s you.')
@section('sub', 'The admin area needs your password again. It stays unlocked for two hours.')

@section('form')
<form method="POST" action="{{ route('admin.login.store') }}" class="card" data-once>
    @csrf

    <p class="small muted" style="margin-bottom:var(--s-4)">
        Signed in as {{ auth()->user()->email }}
    </p>

    <div class="field">
        <label for="password">Password</label>
        <div class="field-reveal">
            <input class="input" id="password" name="password" type="password"
                   autocomplete="current-password" required autofocus
                   @error('password') aria-invalid="true" @enderror>
            @include('partials.password-toggle', ['for' => 'password'])
        </div>
    </div>

    <button class="btn btn-full" type="submit" data-busy="Checking…">Unlock admin</button>
</form>

<p class="small muted center" style="margin-top:var(--s-5)">
    <a class="link-back" href="{{ route('today') }}">Back to the app</a>
</p>
@endsection
