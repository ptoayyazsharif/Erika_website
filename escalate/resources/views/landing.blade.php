@extends('layouts.auth', ['title' => 'Escalate'])

@section('heading', 'Imagine it forward.')
@section('sub', 'Understand it backward. Remember what came true.')

@section('form')
{{-- The landing page borrows the auth layout rather than inventing a second
     shell: the same two-column split, the same type, and on a phone the thing
     that matters is first under the thumb. What differs is that the column
     ends in a way in, not a password field. --}}

<div class="card" data-enter>
    <p class="eyebrow">The app in one sentence</p>
    <p style="margin:var(--s-3) 0 0">{{ config('escalate.copy.intro') }}</p>
</div>

<div class="rule">What you get</div>

<div class="card card-quiet" data-enter>
    <div class="stack">
        <p class="small"><strong>Personal stories.</strong> <span class="muted">Name what you want; it comes back written as an ordinary moment inside the life where you already have it.</span></p>
        <p class="small"><strong>Hear it aloud.</strong> <span class="muted">Every story can be narrated, so you can listen instead of read.</span></p>
        <p class="small"><strong>Daily affirmation cards.</strong> <span class="muted">Drawn from what you are actually working toward.</span></p>
        <p class="small"><strong>A gratitude journal.</strong> <span class="muted">Small things and answered prayers, kept.</span></p>
        <p class="small"><strong>Rewind your journey.</strong> <span class="muted">Look back and see how each moment was leading here.</span></p>
    </div>
</div>

<div class="card" data-enter>
    <p class="eyebrow">Private, by construction</p>
    <p class="small muted" style="margin:var(--s-3) 0 0">
        Everything you write is encrypted before it is stored. Your journal is
        yours; you can export all of it or delete all of it, whenever you like.
        <a href="{{ route('privacy') }}">What happens to what you write &rarr;</a>
    </p>
</div>

{{-- The threshold, marked. The rule above it stays plain, so the change of
     colour is the thing that says this section is different. --}}
<div class="rule rule-iridescent">Getting in</div>

<div class="card card-raised" data-enter>
    <p class="eyebrow">Private beta</p>
    <p class="small muted" style="margin:var(--s-3) 0 var(--s-5)">
        {{ config('escalate.copy.not_launched') }}
    </p>

    <a class="btn btn-full" href="{{ route('apply') }}">Request private access</a>

    <p class="small faint" style="margin:var(--s-4) 0 0">
        Spots are limited. Quality feedback matters more than quantity.
    </p>
</div>

<p class="small muted center" style="margin-top:var(--s-6)">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
    · <a href="{{ route('register') }}">{{ config('escalate.beta.invite_only') ? 'I have an invite code' : 'Create one' }}</a>
</p>
@endsection
