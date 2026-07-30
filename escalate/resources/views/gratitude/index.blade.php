@extends('layouts.app', ['title' => 'Gratitude'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Gratitude</p>
    <h1>What was good.</h1>
    <p class="lede">A line a day is enough. The point is the noticing, not the writing.</p>
</div>

{{-- ── streak ───────────────────────────────────────────────────────────
     Present, but quiet. A streak should reward returning, never punish
     missing — so there is no "don't break it!", no red, and no counter
     resetting in the user's face. --}}
<div class="card" data-enter>
    <div class="row-between wrap" style="gap:var(--s-4)">
        <div>
            <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $streak }}</span>
            <span class="small muted">{{ Str::plural('day', $streak) }} in a row</span>
        </div>

        <div class="row" style="gap:6px" role="list" aria-label="This week">
            @foreach ($week as $day)
                <span role="listitem"
                      class="week-dot {{ $day['written'] ? 'is-written' : '' }} {{ $day['today'] ? 'is-today' : '' }} {{ $day['future'] ? 'is-future' : '' }}"
                      title="{{ $day['label'] }}{{ $day['written'] ? ' — written' : '' }}"
                      aria-label="{{ $day['label'] }}{{ $day['written'] ? ', written' : ', nothing yet' }}">
                    {{ $day['letter'] }}
                </span>
            @endforeach
        </div>
    </div>
</div>

{{-- ── today ────────────────────────────────────────────────────────────── --}}

<div class="rule">Today</div>

<form method="POST" action="{{ route('gratitude.store') }}" class="card" data-once data-enter>
    @csrf

    <div class="field">
        <label class="label" for="body">What are you grateful for today?</label>
        <textarea class="textarea" id="body" name="body" required minlength="2" maxlength="2000"
                  data-autogrow data-counter="g-count" style="min-height:96px"
                  aria-describedby="g-count"
                  placeholder="Small and specific beats large and vague. The coffee was good. She called back.">{{ old('body') }}</textarea>
        <div class="counter" id="g-count"></div>
    </div>

    <div class="field">
        <span class="label">Tag it</span>
        <span class="hint">Optional. Tags are how you find things again.</span>
        <div class="chips">
            @foreach (\App\Http\Controllers\GratitudeController::SUGGESTED_TAGS as $suggestion)
                <label class="chip">
                    <input type="checkbox" name="tags[]" value="{{ $suggestion }}"
                           style="position:absolute;opacity:0;pointer-events:none">
                    {{ ucfirst($suggestion) }}
                </label>
            @endforeach
        </div>
    </div>

    @if ($desires->isNotEmpty())
        <div class="field">
            <label class="label" for="desire_id">Is this part of something you named?</label>
            <select class="select" id="desire_id" name="desire_id">
                <option value="">Not connected to anything</option>
                @foreach ($desires as $desire)
                    <option value="{{ $desire->id }}">{{ Str::limit($desire->title, 60) }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <button class="btn" type="submit" data-busy="Keeping…">
        @include('partials.icon', ['name' => 'heart', 'size' => 16])
        Keep it
    </button>
</form>

@if ($today->isNotEmpty())
    <p class="small muted center" style="margin-top:var(--s-4)">
        {{ $today->count() }} {{ Str::plural('entry', $today->count()) }} kept today. Add another whenever.
    </p>
@endif

{{-- ── history ──────────────────────────────────────────────────────────── --}}

<div class="rule">Your journey</div>

<form method="GET" action="{{ route('gratitude.index') }}" class="stack" data-enter>
    <div class="row" style="gap:var(--s-3);align-items:stretch">
        <input class="input grow" type="search" name="q" value="{{ $search }}"
               placeholder="Search everything you have kept" aria-label="Search your gratitude entries">
        <button class="btn btn-icon" type="submit" aria-label="Search">
            @include('partials.icon', ['name' => 'search', 'size' => 18])
        </button>
    </div>

    @if ($tags->isNotEmpty())
        <div class="chips">
            <a class="chip {{ $tag === '' ? 'is-on' : '' }}"
               href="{{ route('gratitude.index', array_filter(['q' => $search])) }}">All</a>
            @foreach ($tags as $name => $count)
                <a class="chip {{ $tag === $name ? 'is-on' : '' }}"
                   href="{{ route('gratitude.index', array_filter(['tag' => $name, 'q' => $search])) }}">
                    {{ ucfirst($name) }} <span class="faint">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    @endif
</form>

@if ($search !== '' || $tag !== '')
    <p class="small muted" style="margin-top:var(--s-4)">
        {{ $total }} {{ Str::plural('entry', $total) }}
        @if ($search !== '') matching “{{ $search }}” @endif
        @if ($tag !== '') tagged {{ $tag }} @endif
        · <a href="{{ route('gratitude.index') }}">clear</a>
    </p>
@endif

@if ($truncated)
    <p class="small muted" style="margin-top:var(--s-4)">
        Showing your most recent {{ $window }} entries. Narrow it with a tag or a
        search word to reach older ones.
    </p>
@endif

@forelse ($grouped as $date => $entries)
    <div style="margin-top:var(--s-6)" data-enter>
        <p class="eyebrow" style="margin-bottom:var(--s-3)">
            {{ \Illuminate\Support\Carbon::parse($date)->isToday()
                ? 'Today'
                : (\Illuminate\Support\Carbon::parse($date)->isYesterday()
                    ? 'Yesterday'
                    : \Illuminate\Support\Carbon::parse($date)->format('j F Y')) }}
        </p>

        @foreach ($entries as $entry)
            <div class="card card-quiet" style="border-left:2px solid var(--accent-quiet);border-radius:0 var(--r-md) var(--r-md) 0;margin-bottom:var(--s-3)">
                <div class="prose" style="font-size:var(--t-base)">{{ $entry->body }}</div>

                @if (filled($entry->tags) || $entry->desire)
                    <div class="chips" style="margin-top:var(--s-4)">
                        @foreach ($entry->tags ?? [] as $t)
                            <a class="chip" href="{{ route('gratitude.index', ['tag' => \App\Http\Controllers\GratitudeController::normaliseTag($t)]) }}">{{ ucfirst($t) }}</a>
                        @endforeach
                        @if ($entry->desire)
                            <a class="chip" href="{{ route('desires.show', $entry->desire) }}">
                                @include('partials.icon', ['name' => 'compass', 'size' => 13])
                                {{ Str::limit($entry->desire->title, 30) }}
                            </a>
                        @endif
                    </div>
                @endif

                <form method="POST" action="{{ route('gratitude.destroy', $entry) }}"
                      data-confirm="Remove this entry?" style="margin-top:var(--s-3)">
                    @csrf
                    @method('DELETE')
                    <button class="btn-text small faint" type="submit"
                            style="background:none;border:0;cursor:pointer;padding:0;color:var(--text-faint);font:inherit">
                        Remove
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@empty
    <div class="empty" style="margin-top:var(--s-5)" data-enter>
        @include('partials.icon', ['name' => 'heart', 'size' => 34])
        <h3>{{ $search !== '' || $tag !== '' ? 'Nothing matches' : 'Nothing kept yet' }}</h3>
        <p>
            {{ $search !== '' || $tag !== ''
                ? 'Try a different word, or clear the filter.'
                : 'Start with today. One line is a complete entry.' }}
        </p>
        @if ($search !== '' || $tag !== '')
            <a class="btn btn-ghost" href="{{ route('gratitude.index') }}">Clear</a>
        @endif
    </div>
@endforelse
@endsection
