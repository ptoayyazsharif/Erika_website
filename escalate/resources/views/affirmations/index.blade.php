@extends('layouts.app', ['title' => 'Cards'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Today</p>
    <h1>Your cards.</h1>
    <p class="lede">
        Five sentences drawn from what you are actually working toward. Turn one
        over to see what it stands on.
    </p>
</div>

@if ($set?->isReady())
    <div class="stack" data-enter>
        @foreach ($set->affirmations as $card)
            {{-- A real <details>, so turning a card over needs no JavaScript
                 and survives a failed script load. --}}
            <details class="card affirm-card">
                <summary>
                    <span class="serif affirm-front">{{ $card->body }}</span>
                    @if ($card->back)
                        <span class="small faint affirm-hint">What this stands on &rarr;</span>
                    @endif
                </summary>

                @if ($card->back)
                    <p class="small muted" style="margin:var(--s-4) 0 0">{{ $card->back }}</p>
                @endif

                @if ($card->desire)
                    <p class="small faint" style="margin:var(--s-2) 0 0">
                        From <a href="{{ route('desires.show', $card->desire) }}">{{ $card->desire->title }}</a>
                    </p>
                @endif
            </details>

            <form method="POST" action="{{ route('affirmations.favourite', $card) }}" style="margin-top:calc(var(--s-2) * -1)">
                @csrf
                <button class="chip {{ $card->favourite ? 'is-on' : '' }}" type="submit">
                    {{ $card->favourite ? 'Kept' : 'Keep this one' }}
                </button>
            </form>
        @endforeach
    </div>

@elseif ($set?->isPending())
    {{-- cards.js polls and reloads when the set lands. Without JavaScript the
         sentence below is still true and refreshing still works. --}}
    <div class="card card-raised" data-enter
         data-cards-poll="{{ route('affirmations.state') }}">
        <p class="eyebrow">Drawing</p>
        <p style="margin:var(--s-3) 0 0">
            Your cards are being written. This takes a few seconds — the page
            will fill in on its own, and refreshing is safe.
        </p>
    </div>

@elseif ($set?->state === 'failed')
    <div class="notice notice-warn" role="status" data-enter>
        @include('partials.icon', ['name' => 'alert', 'size' => 18])
        <div>{{ $set->failure_reason ?? 'The cards could not be drawn just now.' }}</div>
    </div>

    <form method="POST" action="{{ route('affirmations.store') }}" data-once style="margin-top:var(--s-5)">
        @csrf
        <button class="btn" type="submit" data-busy="Drawing…">Try again</button>
    </form>

@else
    <div class="card card-raised" data-enter>
        <p class="eyebrow">Not drawn yet</p>
        <p style="margin:var(--s-3) 0 var(--s-5)">
            @if ($remaining > 0)
                Draw today's cards. They come from your desires and your world,
                so the more you have written there, the less they sound like
                anybody else's.
            @else
                {{ \App\Support\Quota::message(auth()->user(), 'affirmation') }}
            @endif
        </p>

        @if ($remaining > 0)
            <form method="POST" action="{{ route('affirmations.store') }}" data-once>
                @csrf
                <button class="btn btn-full" type="submit" data-busy="Drawing…">Draw today's cards</button>
            </form>
            <p class="small faint" style="margin:var(--s-4) 0 0">
                {{ $remaining }} {{ Str::plural('draw', $remaining) }} left today.
            </p>
        @endif
    </div>
@endif

@if ($favourites->isNotEmpty())
    <div class="rule">Kept</div>

    <div class="stack" data-enter>
        @foreach ($favourites as $card)
            <div class="card card-quiet">
                <p class="serif" style="margin:0">{{ $card->body }}</p>
                <p class="small faint" style="margin:var(--s-2) 0 0">{{ $card->created_at->format('j M Y') }}</p>
            </div>
        @endforeach
    </div>
@endif

@if ($recent->isNotEmpty())
    <div class="rule">Earlier days</div>

    <div class="stack" data-enter>
        @foreach ($recent as $day)
            <details class="card card-quiet">
                <summary>
                    <span>{{ $day->for_date->format('l, j F') }}</span>
                    <span class="small faint"> · {{ $day->affirmations->count() }} cards</span>
                </summary>
                @foreach ($day->affirmations as $card)
                    <p class="small" style="margin:var(--s-3) 0 0">{{ $card->body }}</p>
                @endforeach
            </details>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
    <script src="{{ asset_v('js/cards.js') }}" defer></script>
@endpush
