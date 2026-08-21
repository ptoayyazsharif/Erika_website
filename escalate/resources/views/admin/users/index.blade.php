@extends('layouts.app', ['title' => 'People'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>People</h1>
    <p class="lede">
        Accounts and what they have generated. Nothing anyone has written is
        shown here or anywhere else in this area.
    </p>
</div>

@include('admin._nav')

<form method="GET" action="{{ route('admin.users') }}" data-enter>
    <div class="row" style="gap:var(--s-3);align-items:stretch;margin-bottom:var(--s-5)">
        <input class="input grow" type="search" name="q" value="{{ $search }}"
               placeholder="Search name or email" aria-label="Search people">
        <button class="btn btn-icon" type="submit" aria-label="Search">
            @include('partials.icon', ['name' => 'search', 'size' => 18])
        </button>
    </div>
</form>

@forelse ($users as $person)
    <a class="card" href="{{ route('admin.users.show', $person) }}" data-enter>
        <div class="row-between" style="margin-bottom:var(--s-2)">
            <h3 style="margin:0;font-size:var(--t-md)">{{ $person->name }}</h3>
            <div class="row" style="gap:var(--s-2)">
                @if ($person->suspended_at)<span class="pill" style="color:var(--danger)">suspended</span>@endif
                @if ($person->plan_override)<span class="pill">{{ $person->plan_override }}</span>@endif
                @if ($person->isAdmin())<span class="pill">admin</span>@endif
            </div>
        </div>
        <p class="small muted" style="margin:0">{{ $person->email }}</p>
        <p class="small faint" style="margin:var(--s-3) 0 0">
            Joined {{ $person->created_at->diffForHumans() }} ·
            {{ $person->desires_count }} {{ Str::plural('desire', $person->desires_count) }} ·
            {{ $person->stories_count }} {{ Str::plural('reading', $person->stories_count) }} ·
            {{ $person->last_login_at ? 'last seen '.$person->last_login_at->diffForHumans() : 'never signed in' }}
        </p>
    </a>
@empty
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'compass', 'size' => 34])
        <h3>{{ $search !== '' ? 'Nobody matches' : 'Nobody yet' }}</h3>
        <p>{{ $search !== '' ? 'Try a different word.' : 'Hand out an invite and someone can sign up.' }}</p>
    </div>
@endforelse

@include('partials.pager', ['paginator' => $users])
@endsection
