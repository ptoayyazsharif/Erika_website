@extends('layouts.app', ['title' => 'My Rewinds'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">My Rewinds</p>
    <h1>Looking back</h1>
    <p class="lede">
        When something has finished moving, you write down what actually happened.
        Your words — the app only puts them in order afterwards.
    </p>
</div>

{{-- Desires that have finished and have nothing written about them. This is
     the whole reason to be on this screen, so it goes above the archive. --}}
@if ($waiting->isNotEmpty())
    <h2 class="h-sm" style="margin-bottom:var(--s-4)">Ready to look back at</h2>

    @foreach ($waiting as $desire)
        <a class="card" href="{{ route('rewinds.create', $desire) }}" data-enter>
            <div class="row-between" style="margin-bottom:var(--s-3)">
                <span class="pill pill-{{ $desire->status }}">{{ $desire->statusLabel() }}</span>
                <span class="small faint">{{ $desire->status_changed_at?->diffForHumans() }}</span>
            </div>
            <h3 style="margin-bottom:var(--s-2)">{{ $desire->title }}</h3>
            <p class="small muted" style="margin:0">Begin a Rewind.</p>
        </a>
    @endforeach
@endif

@forelse ($rewinds as $rewind)
    @if ($loop->first && $waiting->isNotEmpty())
        <h2 class="h-sm" style="margin:var(--s-7) 0 var(--s-4)">Written</h2>
    @endif

    <a class="card" href="{{ route('rewinds.show', $rewind) }}" data-enter>
        <div class="row-between" style="margin-bottom:var(--s-3)">
            <span class="pill pill-{{ $rewind->desire?->status }}">{{ $rewind->desire?->statusLabel() }}</span>
            <span class="small faint">{{ $rewind->updated_at->diffForHumans() }}</span>
        </div>
        <h3 style="margin-bottom:var(--s-2)">{{ $rewind->desire?->title ?? 'A rewind' }}</h3>
        <p class="small muted" style="margin:0">
            @if ($rewind->isReady())
                Written.
            @elseif ($rewind->isPending())
                Being written…
            @elseif ($rewind->state === 'failed')
                Needs another try.
            @else
                {{ count($rewind->answered()) }} of 6 questions answered.
            @endif
        </p>
    </a>
@empty
    @if ($waiting->isEmpty())
        <div class="empty" data-enter>
            @include('partials.icon', ['name' => 'rewind', 'size' => 34])
            <h3>Nothing to look back at yet</h3>
            <p>
                A Rewind opens once a desire has finished moving — when it arrived,
                changed shape, or you set it down. Mark one as complete and it will
                appear here.
            </p>
            <a class="btn btn-ghost" href="{{ route('desires.index') }}">Go to your desires</a>
        </div>
    @endif
@endforelse
@endsection
