@extends('layouts.auth', ['title' => 'Create an account'])

@section('heading', 'Begin.')
@section('sub', 'Fifteen minutes from now you will have your first reading in your own voice.')

@section('form')
<form method="POST" action="{{ route('register.store') }}" class="card" data-once>
    @csrf

    @if (config('escalate.beta.invite_only'))
        <div class="field">
            <label for="invite">Invite code</label>
            <span class="hint">Escalate is invite-only while it is being tested. The code came with your invitation — dashes and capitals are optional.</span>
            <input class="input" id="invite" name="invite" type="text" required
                   autocomplete="off" autocapitalize="characters" spellcheck="false"
                   maxlength="32" value="{{ old('invite', $invite) }}"
                   placeholder="ABCD-EFGH-JKMN"
                   @error('invite') aria-invalid="true" @enderror
                   @if (! old('invite', $invite)) autofocus @endif>
        </div>
    @endif

    <div class="field">
        <label for="name">What should we call you?</label>
        <input class="input" id="name" name="name" type="text" autocomplete="name"
               required maxlength="80" value="{{ old('name') }}"
               @error('name') aria-invalid="true" @enderror
               @if (! config('escalate.beta.invite_only') || old('invite', $invite)) autofocus @endif>
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
        <div class="field-reveal">
            <input class="input" id="password" name="password" type="password"
                   autocomplete="new-password" required
                   @error('password') aria-invalid="true" @enderror>
            @include('partials.password-toggle', ['for' => 'password'])
        </div>
    </div>

    <div class="field">
        <label for="password_confirmation">Password again</label>
        <div class="field-reveal">
            <input class="input" id="password_confirmation" name="password_confirmation"
                   type="password" autocomplete="new-password" required>
            @include('partials.password-toggle', ['for' => 'password_confirmation'])
        </div>
    </div>

    <label class="option {{ old('agree') ? 'is-on' : '' }}" style="margin-bottom:var(--s-3)">
        <input type="checkbox" name="agree" value="1" @checked(old('agree'))>
        <span class="tick" aria-hidden="true"></span>
        <span class="option-body">
            <span class="option-label">I’ve read what happens to what I write</span>
            <small>
                Your words are sent to two AI companies to be written and read aloud.
                <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Read it first</a> —
                it’s short, and it matters.
            </small>
        </span>
    </label>

    <label class="option {{ old('age') ? 'is-on' : '' }}" style="margin-bottom:var(--s-5)">
        <input type="checkbox" name="age" value="1" @checked(old('age'))>
        <span class="tick" aria-hidden="true"></span>
        <span class="option-body">
            <span class="option-label">I’m 16 or over</span>
        </span>
    </label>

    <button class="btn btn-full" type="submit" data-busy="Creating…">Create my account</button>
</form>

<p class="small muted center" style="margin-top:var(--s-5)">
    Already have one? <a href="{{ route('login') }}">Sign in</a>
</p>
@endsection
