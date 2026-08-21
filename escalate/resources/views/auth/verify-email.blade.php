@extends('layouts.auth', ['title' => 'Confirm your email'])

@section('heading', 'One thing first.')
@section('sub', 'We sent a link to ' . auth()->user()->email . '. Open it and everything is yours.')

@section('form')
<div class="card">
    <p class="small muted" style="margin-bottom:var(--s-5)">
        You can still look around, fill in My World and name a desire without
        this. Confirming is what opens the writing and the voice — those cost
        real money to produce, so they wait until we know the address is yours.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" data-once>
        @csrf
        <button class="btn btn-full" type="submit" data-busy="Sending…">Send it again</button>
    </form>

    <p class="small faint center" style="margin-top:var(--s-4);margin-bottom:0">
        It usually arrives within a minute. Check your spam folder — a first
        email from a new domain very often lands there.
    </p>
</div>

<div class="row wrap" style="margin-top:var(--s-5);gap:var(--s-4);justify-content:center">
    <a class="btn btn-quiet btn-sm" href="{{ route('today') }}">Carry on without it</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-quiet btn-sm" type="submit">Sign out</button>
    </form>
</div>
@endsection
