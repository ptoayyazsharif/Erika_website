@extends('layouts.auth', ['title' => 'Offline'])

@section('heading', 'You’re offline.')
@section('sub', 'Escalate keeps nothing on your device — not your stories, not your gratitude, not your audio. That is on purpose, and it means this screen is all there is until you reconnect.')

@section('form')
<div class="card">
    <p class="small muted" style="margin-bottom:var(--s-5)">
        Cache Storage sits in plaintext on the device and survives signing out.
        A private journal does not belong there, so nothing you have written
        was ever copied into it.
    </p>
    <a class="btn btn-full" href="{{ route('today') }}">Try again</a>
</div>
@endsection
