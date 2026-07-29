@extends('layouts.app', ['title' => Str::limit($desire->title, 40)])

@section('content')
<div class="page-head" data-enter-hero>
    <span class="pill pill-{{ $desire->status }}" style="margin-bottom:var(--s-3)">{{ $desire->statusLabel() }}</span>
    <h1>{{ $desire->title }}</h1>
    <p class="small faint">
        Named {{ $desire->created_at->format('j F Y') }}
        @if ($desire->completed_at) · {{ $desire->statusLabel() }} {{ $desire->completed_at->format('j F Y') }} @endif
    </p>
</div>

@if (filled($desire->description))
    <div class="card" data-enter>
        <p class="eyebrow">In your words</p>
        <div class="prose">{!! nl2br(e($desire->description)) !!}</div>
    </div>
@endif

@if (filled($desire->why_it_matters))
    <div class="card card-quiet" data-enter>
        <p class="eyebrow">Why it matters</p>
        <div class="prose">{!! nl2br(e($desire->why_it_matters)) !!}</div>
    </div>
@endif

@if (filled($desire->desired_feelings) || filled($desire->people_involved) || filled($desire->non_negotiables))
    <div class="card card-quiet" data-enter>
        @if (filled($desire->desired_feelings))
            <p class="eyebrow">How it feels</p>
            <div class="chips" style="margin-bottom:var(--s-5)">
                @foreach ($desire->desired_feelings as $feeling)
                    <span class="chip">{{ ucfirst($feeling) }}</span>
                @endforeach
            </div>
        @endif
        @if (filled($desire->people_involved))
            <p class="eyebrow">Who is in it</p>
            <div class="chips" style="margin-bottom:var(--s-5)">
                @foreach ($desire->people_involved as $person)
                    <span class="chip">{{ $person }}</span>
                @endforeach
            </div>
        @endif
        @if (filled($desire->non_negotiables))
            <p class="eyebrow">Non-negotiable</p>
            <p class="prose" style="margin:0">{{ $desire->non_negotiables }}</p>
        @endif
    </div>
@endif

{{-- ── the reading ──────────────────────────────────────────────────────── --}}

<div class="rule">Readings</div>

<form method="POST" action="{{ route('stories.store', $desire) }}" data-once data-enter>
    @csrf
    <button class="btn btn-full" type="submit" data-busy="Writing…">
        @include('partials.icon', ['name' => 'feather', 'size' => 17])
        {{ $stories->isEmpty() ? 'Write my reading' : 'Write another reading' }}
    </button>
</form>

@forelse ($stories as $story)
    <a class="card" href="{{ route('stories.show', $story) }}" data-enter style="margin-top:var(--s-4)">
        <div class="row-between">
            <div class="grow">
                <p class="small faint" style="margin:0 0 4px">{{ $story->created_at->format('j M Y, H:i') }}</p>
                <p class="serif" style="margin:0;font-size:var(--t-md)">
                    @if ($story->isReady())
                        {{ Str::limit(Str::before($story->text(), '.'), 72) }}…
                    @elseif ($story->isPending())
                        <span class="muted">Being written…</span>
                    @else
                        <span class="muted">Did not finish</span>
                    @endif
                </p>
            </div>
            @include('partials.icon', ['name' => 'chevron-right', 'size' => 18])
        </div>
        @if ($story->isReady())
            <p class="small faint" style="margin:var(--s-3) 0 0">
                {{ $story->words() }} words
                @if ($story->isEdited()) · edited @endif
                @if ($story->play_count) · played {{ $story->play_count }}× @endif
            </p>
        @endif
    </a>
@empty
    <p class="small muted center" style="margin-top:var(--s-5)">No readings yet for this one.</p>
@endforelse

{{-- ── status ───────────────────────────────────────────────────────────── --}}

<div class="rule">Where it stands</div>

<form method="POST" action="{{ route('desires.status', $desire) }}" data-enter>
    @csrf
    @method('PATCH')
    <div class="options">
        @foreach (config('escalate.statuses') as $key => $status)
            <button class="option {{ $desire->status === $key ? 'is-on' : '' }}"
                    type="submit" name="status" value="{{ $key }}">
                <span class="tick" aria-hidden="true"></span>
                <span class="option-body">
                    <span class="option-label">{{ $status['label'] }}</span>
                    <small>{{ $status['note'] }}</small>
                </span>
            </button>
        @endforeach
    </div>
</form>

@if ($desire->isComplete())
    <div class="card card-raised" style="margin-top:var(--s-5)" data-enter>
        <p class="eyebrow">Rewind</p>
        <h3 style="margin-bottom:var(--s-2)">{{ $desire->rewind ? 'You have written a Rewind for this.' : 'Look back at it.' }}</h3>
        <p class="small muted">A Rewind walks you through what actually happened — the turning points, the redirections, who was part of it — and keeps it.</p>
        <a class="btn btn-ghost btn-sm" href="{{ route('rewinds.index') }}">
            {{ $desire->rewind ? 'Open the Rewind' : 'Begin a Rewind' }}
        </a>
    </div>
@endif

<div class="row wrap" style="margin-top:var(--s-7);gap:var(--s-3)">
    <a class="btn btn-quiet" href="{{ route('desires.edit', $desire) }}">
        @include('partials.icon', ['name' => 'edit', 'size' => 16]) Edit
    </a>
    <form method="POST" action="{{ route('desires.destroy', $desire) }}"
          data-confirm="Delete this desire and every reading written for it? This cannot be undone.">
        @csrf
        @method('DELETE')
        <button class="btn btn-quiet" type="submit" style="color:var(--danger)">
            @include('partials.icon', ['name' => 'trash', 'size' => 16]) Delete
        </button>
    </form>
</div>
@endsection
