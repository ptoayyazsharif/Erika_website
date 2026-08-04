@extends('layouts.app', ['title' => 'A reading'])

@section('content')
<div id="reading"
     data-reading
     data-state-url="{{ route('stories.state', $story) }}"
     data-played-url="{{ route('stories.played', $story) }}"
     data-state="{{ $story->state }}"
     data-audio-state="{{ $narration?->state ?? 'none' }}"
     data-audio-url="{{ $narration?->isReady() ? route('media.narration', $narration) : '' }}">

    {{-- ── waiting ──────────────────────────────────────────────────────── --}}

    <section data-phase="waiting" @if (! $story->isPending()) hidden @endif
             style="min-height:60vh;display:grid;place-items:center;text-align:center">
        <div>
            <p class="eyebrow">Reading your intentions</p>
            <h2 class="serif" data-waiting-line style="margin-bottom:var(--s-6);max-width:24ch">Listening for the names you gave me…</h2>
            <div class="progress" style="max-width:14rem;margin:0 auto"><i data-waiting-bar></i></div>
        </div>
    </section>

    {{-- ── failed ───────────────────────────────────────────────────────── --}}

    <section data-phase="failed" @if ($story->state !== 'failed') hidden @endif>
        <div class="notice notice-error" role="alert">
            @include('partials.icon', ['name' => 'alert', 'size' => 18])
            <div data-failure>{{ $story->failure_reason ?: 'The reading could not be written.' }}</div>
        </div>
        <form method="POST" action="{{ route('stories.regenerate', $story) }}" data-once>
            @csrf
            <button class="btn" type="submit" data-busy="Trying again…">Try again</button>
        </form>
    </section>

    {{-- ── the reading ──────────────────────────────────────────────────── --}}

    <section data-phase="ready" @if (! $story->isReady()) hidden @endif>
        <div class="page-head" data-enter-hero>
            {{-- A desire title is a sentence, not a label: set in the serif at
                 normal case rather than as an uppercase eyebrow, which turns a
                 long title into two ragged lines of shouting. --}}
            <h1 class="sr-only">Your reading</h1>
            @if (filled($story->desire?->title))
                <p class="serif muted" style="font-size:var(--t-base);margin-bottom:var(--s-2)">
                    {{ $story->desire->title }}
                </p>
            @endif
            <p class="small faint">
                <span data-word-count>{{ $story->words() }}</span> words
                @if ($story->isEdited()) · edited by you @endif
            </p>
        </div>

        <article class="prose prose-lg" data-lines>
            @foreach ($story->paragraphs() as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </article>

        {{-- ── player ──────────────────────────────────────────────────── --}}

        <div class="card card-raised" style="margin-top:var(--s-7)" data-player hidden>
            <div class="row" style="gap:var(--s-4);margin-bottom:var(--s-5)">
                <button class="btn btn-icon" type="button" data-play
                        style="width:56px;height:56px;background:var(--accent);border-color:transparent;color:var(--on-accent)">
                    <span data-icon-play>@include('partials.icon', ['name' => 'play', 'size' => 20])</span>
                    <span data-icon-pause hidden>@include('partials.icon', ['name' => 'pause', 'size' => 20])</span>
                    <span class="sr-only">Play or pause</span>
                </button>

                <button class="btn btn-icon" type="button" data-loop aria-pressed="false" title="Loop the reading">
                    @include('partials.icon', ['name' => 'loop', 'size' => 18])
                    <span class="sr-only">Loop</span>
                </button>

                <div class="grow">
                    <div class="progress"><i data-audio-bar></i></div>
                    <div class="row-between small faint" style="margin-top:6px">
                        <span data-elapsed>0:00</span>
                        <span data-duration>{{ gmdate('i:s', $story->estimatedSeconds()) }}</span>
                    </div>
                </div>
            </div>

            <div class="row-between wrap" style="gap:var(--s-3)">
                <div class="row" style="gap:var(--s-2)">
                    <span class="small muted">Sleep timer</span>
                    @foreach ([0 => 'Off', 10 => '10m', 20 => '20m', 45 => '45m'] as $mins => $label)
                        <button class="chip {{ $mins === 0 ? 'is-on' : '' }}" type="button"
                                data-sleep="{{ $mins }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <p class="small faint" data-sleep-note hidden style="margin:var(--s-3) 0 0"></p>
        </div>

        {{-- narration not rendered yet --}}
        <div style="margin-top:var(--s-6)" data-narrate-block @if ($narration?->isReady()) hidden @endif>
            @if ($narration?->isPending())
                <p class="small muted row" data-narrate-pending style="gap:var(--s-2)">
                    @include('partials.icon', ['name' => 'timer', 'size' => 16])
                    <span>The voice is being recorded…</span>
                </p>
            @elseif ($narration?->state === 'failed')
                <div class="notice notice-warn">
                    @include('partials.icon', ['name' => 'alert', 'size' => 18])
                    <div>{{ $narration->failure_reason }}</div>
                </div>
            @endif

            {{-- Always rendered, hidden while a narration is in flight. If the
                 render then fails, reading.js un-hides this rather than leaving
                 the screen stuck on "being recorded" with no way forward. --}}
            <form method="POST" action="{{ route('stories.narrate', $story) }}" data-once
                  @if ($narration?->isPending()) hidden @endif>
                @csrf
                <button class="btn btn-ghost btn-full" type="submit" data-busy="Recording…"
                        @if ($remaining['narration'] < 1) aria-disabled="true" disabled @endif>
                    @include('partials.icon', ['name' => 'play', 'size' => 16])
                    Hear it in your voice
                </button>
            </form>
            <p class="small faint center" style="margin-top:var(--s-3)"
               @if ($narration?->isPending()) hidden @endif>
                {{ $remaining['narration'] }} {{ Str::plural('narration', $remaining['narration']) }} left today
            </p>
        </div>

        {{-- ── actions ─────────────────────────────────────────────────── --}}

        <div class="rule">This reading</div>

        <div class="row wrap" style="gap:var(--s-3)">
            <form method="POST" action="{{ route('stories.favourite', $story) }}">
                @csrf
                <button class="btn btn-quiet" type="submit">
                    @include('partials.icon', ['name' => 'heart', 'size' => 16])
                    {{ $story->favourite ? 'Saved' : 'Save it' }}
                </button>
            </form>

            <a class="btn btn-quiet" href="{{ route('stories.edit', $story) }}">
                @include('partials.icon', ['name' => 'edit', 'size' => 16]) Change a word
            </a>

            <form method="POST" action="{{ route('stories.regenerate', $story) }}" data-once
                  data-confirm="Write this again from scratch? The current words and narration are replaced.">
                @csrf
                <button class="btn btn-quiet" type="submit"
                        @if ($remaining['story'] < 1) aria-disabled="true" disabled @endif>
                    @include('partials.icon', ['name' => 'loop', 'size' => 16]) Write it again
                </button>
            </form>

            <form method="POST" action="{{ route('stories.destroy', $story) }}"
                  data-confirm="Delete this reading?">
                @csrf
                @method('DELETE')
                <button class="btn btn-quiet" type="submit" style="color:var(--danger)">
                    @include('partials.icon', ['name' => 'trash', 'size' => 16]) Delete
                </button>
            </form>
        </div>

        @if ($story->desire)
            <p class="small muted" style="margin-top:var(--s-6)">
                <a class="link-back" href="{{ route('desires.show', $story->desire) }}">Back to {{ Str::limit($story->desire->title, 40) }}</a>
            </p>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset_v('js/reading.js') }}" defer></script>
@endpush
