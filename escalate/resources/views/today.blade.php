@extends('layouts.app', ['title' => 'Today'])

@section('content')
{{-- A week in, once, and never in the way. Dismissing it hides it for this
     session; answering hides it for good. There is no version of this that
     stands between somebody and their own journal. --}}
@if (\App\Http\Controllers\FeedbackController::isDue($user, request()))
    <div class="notice" role="status" data-enter>
        @include('partials.icon', ['name' => 'sparkle', 'size' => 18])
        <div>
            <p style="margin:0">You have had Escalate a week. Would you tell us how it is going?</p>
            <div class="row wrap" style="gap:var(--s-3);margin-top:var(--s-3)">
                <a class="btn btn-sm" href="{{ route('feedback') }}">Four questions</a>
                <form method="POST" action="{{ route('feedback.defer') }}">
                    @csrf
                    <button class="btn btn-quiet btn-sm" type="submit">Not now</button>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="page-head" data-enter-hero>
    <p class="eyebrow">{{ $greeting }}</p>
    <h1>{{ $user->callMe() }}</h1>

    <p class="lede">
        @if ($totals['desires'] === 0)
            Name something you want. Escalate writes it back to you as an ordinary
            moment inside the life where you already have it.
        @elseif ($awaiting)
            One of your desires is still waiting to be written.
        @else
            Everything you have named has been written. Add another when you are ready.
        @endif
    </p>
</div>

{{-- ── the one action ──────────────────────────────────────────────────────
     Exactly one primary button on this screen, and which one it is depends on
     where the person actually is. Offering "name a desire" and "write a
     reading" side by side would put the decision back on them, which is the
     thing that made the old screen useless. --}}

@if ($totals['desires'] === 0)
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'compass', 'size' => 34])
        <h3>Start with one thing</h3>
        <p>A house, a number, a Tuesday morning that goes differently. Specific beats grand.</p>
        <a class="btn" href="{{ route('desires.create') }}">
            @include('partials.icon', ['name' => 'plus', 'size' => 16]) Name your first desire
        </a>
    </div>
@elseif ($awaiting)
    <a class="card" href="{{ route('desires.show', $awaiting) }}" data-enter>
        <p class="eyebrow">Ready to be written</p>
        <h3 style="margin-bottom:var(--s-2)">{{ $awaiting->title }}</h3>
        <p class="small muted" style="margin:0">Open it and ask for your reading.</p>
    </a>
@elseif ($latestStory)
    <a class="card" href="{{ route('stories.show', $latestStory) }}" data-enter>
        <p class="eyebrow">Your most recent reading</p>
        <h3 style="margin-bottom:var(--s-2)">{{ $latestStory->desire?->title ?? 'A reading' }}</h3>
        <p class="small muted" style="margin:0">
            {{ $latestStory->created_at->diffForHumans() }} · read it again
        </p>
    </a>
@endif

{{-- ── where things stand ──────────────────────────────────────────────── --}}

@if ($totals['desires'] > 0)
    <div class="row wrap" style="gap:var(--s-4);margin:var(--s-6) 0" data-enter>
        <a class="chip" href="{{ route('desires.index') }}">
            Desires <span class="faint">{{ $totals['desires'] }}</span>
        </a>
        <a class="chip" href="{{ route('stories.index') }}">
            Readings <span class="faint">{{ $totals['readings'] }}</span>
        </a>
        <a class="chip" href="{{ route('gratitude.index') }}">
            Gratitude <span class="faint">{{ $totals['gratitude'] }}</span>
        </a>
    </div>

    <div class="row-between" style="margin-bottom:var(--s-4)">
        <h2 class="h-sm">Recently named</h2>
        <a class="small muted" href="{{ route('desires.index') }}">All desires</a>
    </div>

    @foreach ($desires as $desire)
        <a class="card" href="{{ route('desires.show', $desire) }}" data-enter>
            <div class="row-between" style="margin-bottom:var(--s-3)">
                <span class="pill pill-{{ $desire->status }}">{{ $desire->statusLabel() }}</span>
                <span class="small faint">{{ $desire->created_at->diffForHumans() }}</span>
            </div>
            <h3 style="margin-bottom:var(--s-2)">{{ $desire->title }}</h3>
            <p class="small faint" style="margin:0">
                {{ $desire->stories->count() === 0
                    ? 'No reading yet'
                    : $desire->stories->count().' '.Str::plural('reading', $desire->stories->count()) }}
            </p>
        </a>
    @endforeach

    <div class="row wrap" style="margin-top:var(--s-5)">
        <a class="btn btn-ghost" href="{{ route('desires.create') }}">
            @include('partials.icon', ['name' => 'plus', 'size' => 16]) Name another desire
        </a>
    </div>
@endif
@endsection
