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
    <p>Coming in the slice that builds {{ $heading }}.</p>
    <a class="btn btn-ghost" href="{{ route('today') }}">Back to Today</a>
</div>

<form method="POST" action="{{ route('logout') }}" data-logout style="margin-top:var(--s-6)">
    @csrf
    <button type="submit" class="btn btn-quiet">Sign out</button>
</form>
@endsection
