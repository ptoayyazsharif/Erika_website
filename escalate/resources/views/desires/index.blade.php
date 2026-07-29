@extends('layouts.app', ['title' => 'Desires'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Manifestation Archive</p>
    <h1>Desires</h1>
    <p class="lede">Everything you have named, and where each one stands.</p>
</div>

<div class="row wrap" style="margin-bottom:var(--s-5)">
    <a class="btn btn-sm" href="{{ route('desires.create') }}">
        @include('partials.icon', ['name' => 'plus', 'size' => 16]) Name a desire
    </a>
</div>

<div class="chips" style="margin-bottom:var(--s-6)" data-enter>
    <a class="chip {{ $filter === '' ? 'is-on' : '' }}" href="{{ route('desires.index') }}">
        All <span class="faint">{{ $counts->sum() }}</span>
    </a>
    @foreach (config('escalate.statuses') as $key => $status)
        @continue(! $counts->has($key))
        <a class="chip {{ $filter === $key ? 'is-on' : '' }}"
           href="{{ route('desires.index', ['status' => $key]) }}">
            {{ $status['label'] }} <span class="faint">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

@forelse ($desires as $desire)
    <a class="card" href="{{ route('desires.show', $desire) }}" data-enter>
        <div class="row-between" style="margin-bottom:var(--s-3)">
            <span class="pill pill-{{ $desire->status }}">{{ $desire->statusLabel() }}</span>
            <span class="small faint">{{ $desire->created_at->diffForHumans() }}</span>
        </div>
        <h3 style="margin-bottom:var(--s-2)">{{ $desire->title }}</h3>
        @if (filled($desire->description))
            <p class="small muted" style="margin:0">{{ Str::limit($desire->description, 130) }}</p>
        @endif
        <div class="row small faint" style="margin-top:var(--s-4);gap:var(--s-4)">
            <span>{{ \App\Http\Controllers\DesireController::CATEGORIES[$desire->category] ?? 'Something else' }}</span>
            <span>{{ $desire->stories_count }} {{ Str::plural('reading', $desire->stories_count) }}</span>
        </div>
    </a>
@empty
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'compass', 'size' => 34])
        <h3>Nothing named yet</h3>
        <p>A desire is the seed of a reading. Name one plainly — the specifics matter more than the wording.</p>
        <a class="btn" href="{{ route('desires.create') }}">Name your first desire</a>
    </div>
@endforelse
@endsection
