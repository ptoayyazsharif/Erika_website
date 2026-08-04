@extends('layouts.app', ['title' => $heading])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Escalate</p>
    <h1>{{ $heading }}</h1>
    <p class="lede">This section is next on the build. The navigation, the shell and the security around it are already in place.</p>
</div>

<div class="empty" data-enter>
    @include('partials.icon', ['name' => 'feather', 'size' => 34])
    <h3>Not written yet</h3>
    {{-- Points at Desires, not Today.

         This view is shared by every unbuilt screen, and Today used to be one
         of them — so it rendered "Back to Today" while you were standing on
         Today, offering a button to the page you were already on. Today is a
         real screen now, but a dead end is still a dead end: whoever lands
         here wanted to do something, and naming a desire is the thing this
         app is for. --}}
    <p>{{ $heading }} is still being built. Everything else works.</p>
    <a class="btn btn-ghost" href="{{ route('desires.index') }}">Go to your desires</a>
</div>

<form method="POST" action="{{ route('logout') }}" data-logout style="margin-top:var(--s-6)">
    @csrf
    <button type="submit" class="btn btn-quiet">Sign out</button>
</form>
@endsection
