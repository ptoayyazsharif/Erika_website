@extends('layouts.app', ['title' => 'My Journey'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">My Journey</p>
    <h1>Everything, in order</h1>
    <p class="lede">
        @if ($stats['desires'] === 0)
            Nothing here yet. Name a desire and this fills in as you go.
        @else
            Since {{ $started->format('j F Y') }} — {{ $stats['days'] }}
            {{ Str::plural('day', $stats['days']) }}.
        @endif
    </p>
</div>

@if ($stats['desires'] === 0)
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'journey', 'size' => 34])
        <h3>Your journey starts with one thing</h3>
        <p>Every desire you name, every reading, every day you note something you are grateful for, appears here in the order it happened.</p>
        <a class="btn" href="{{ route('desires.create') }}">
            @include('partials.icon', ['name' => 'plus', 'size' => 16]) Name your first desire
        </a>
    </div>
@else
    {{-- The four numbers worth knowing, and no others. A dashboard of
         everything countable would bury the one that matters. --}}
    <div class="journey-stats" data-enter>
        <div class="stat">
            <span class="stat-n">{{ $stats['desires'] }}</span>
            <span class="stat-l">{{ Str::plural('desire', $stats['desires']) }} named</span>
        </div>
        <div class="stat">
            <span class="stat-n">{{ $stats['arrived'] }}</span>
            <span class="stat-l">arrived</span>
        </div>
        <div class="stat">
            <span class="stat-n">{{ $stats['readings'] }}</span>
            <span class="stat-l">{{ Str::plural('reading', $stats['readings']) }}</span>
        </div>
        <div class="stat">
            <span class="stat-n">{{ $stats['gratitude'] }}</span>
            <span class="stat-l">gratitude</span>
        </div>
    </div>

    @foreach ($months as $month => $events)
        <h2 class="journey-month">{{ $month }}</h2>

        <ol class="timeline">
            @foreach ($events as $event)
                <li class="timeline-item">
                    <span class="timeline-mark timeline-{{ $event['kind'] }}" aria-hidden="true">
                        @include('partials.icon', ['name' => $event['icon'], 'size' => 15])
                    </span>

                    <a class="timeline-body" href="{{ $event['url'] }}">
                        <span class="timeline-title">{{ $event['title'] }}</span>
                        <span class="small faint">
                            {{ $event['note'] }} · <time datetime="{{ $event['at']->toDateString() }}">{{ $event['at']->format('j M') }}</time>
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    @endforeach
@endif
@endsection
