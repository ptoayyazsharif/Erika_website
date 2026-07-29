@extends('layouts.auth', ['title' => 'Create an account'])

@section('heading', 'Begin.')
@section('sub', 'Fifteen minutes from now you will have your first reading in your own voice.')

@section('form')
<form method="POST" action="{{ route('register.store') }}" class="card" data-once>
    @csrf

    <div class="field">
        <label for="name">What should we call you?</label>
        <input class="input" id="name" name="name" type="text" autocomplete="name"
               required autofocus maxlength="80" value="{{ old('name') }}"
               @error('name') aria-invalid="true" @enderror>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input class="input" id="email" name="email" type="email" inputmode="email"
               autocomplete="username" required value="{{ old('email') }}"
               @error('email') aria-invalid="true" @enderror>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <span class="hint">At least twelve characters, with letters and numbers. This is the only thing standing between your journal and anyone else.</span>
        <input class="input" id="password" name="password" type="password"
               autocomplete="new-password" required
               @error('password') aria-invalid="true" @enderror>
    </div>

    <div class="field">
        <label for="password_confirmation">Password again</label>
        <input class="input" id="password_confirmation" name="password_confirmation"
               type="password" autocomplete="new-password" required>
    </div>

    <button class="btn btn-full" type="submit" data-busy="Creating…">Create my account</button>
</form>

<p class="small muted center" style="margin-top:var(--s-5)">
    Already have one? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
