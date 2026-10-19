@extends('layouts.app', ['title' => 'Applications'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Applications</h1>
    <p class="lede">
        People asking for a seat. Selecting somebody mints an invite bound to
        their address and emails it to them.
    </p>
</div>

@include('admin._nav')

<div class="card" data-enter>
    <div class="row wrap" style="gap:var(--s-6)">
        <div>
            <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $counts['pending'] }}</span>
            <span class="small muted">waiting on you</span>
        </div>
        <div>
            <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $counts['selected'] }}</span>
            <span class="small muted">selected of {{ $seats }}</span>
        </div>
        <div>
            <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $counts['waitlisted'] }}</span>
            <span class="small muted">on the waitlist</span>
        </div>
    </div>

    @if ($counts['selected'] >= $seats)
        <p class="small" style="color:var(--brass);margin:var(--s-4) 0 0">
            The Founding {{ $seats }} is full. Selecting anyone else makes it a
            larger number than the one you promised.
        </p>
    @endif

    <div class="row wrap" style="gap:var(--s-2);margin-top:var(--s-4)">
        <a class="chip {{ $status === null ? 'is-on' : '' }}" href="{{ route('admin.applications') }}">All</a>
        @foreach (['pending' => 'Waiting', 'selected' => 'Selected', 'waitlisted' => 'Waitlist'] as $key => $label)
            <a class="chip {{ $status === $key ? 'is-on' : '' }}"
               href="{{ route('admin.applications', ['status' => $key]) }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

@forelse ($applications as $application)
    <div class="card {{ $application->isPending() ? 'card-raised' : 'card-quiet' }}" data-enter>
        <div class="row-between wrap" style="gap:var(--s-3)">
            <div>
                <a href="{{ route('admin.applications.show', $application) }}">
                    <strong>{{ $application->name }}</strong>
                </a>
                <span class="small faint"> · {{ $application->email }}</span>
            </div>
            <span class="pill">{{ $application->status }}</span>
        </div>

        <p class="small faint" style="margin:var(--s-2) 0 0">
            Applied {{ $application->created_at->diffForHumans() }}
            @if ($application->invite) · code {{ $application->invite->code }} @endif
        </p>
    </div>
@empty
    <div class="card" data-enter>
        <p class="small muted" style="margin:0">
            Nothing here yet. The form is at
            <a href="{{ route('apply') }}">{{ route('apply') }}</a>.
        </p>
    </div>
@endforelse

{{ $applications->links() }}
@endsection
