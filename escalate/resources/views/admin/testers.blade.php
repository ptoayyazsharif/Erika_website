@extends('layouts.app', ['title' => 'Testers'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Testers</h1>
    <p class="lede">
        Everybody you let in, and where they actually got to. The ones needing a
        decision are first, then whoever has been stuck longest.
    </p>
</div>

@include('admin._nav')

@if ($rows->isEmpty())
    <div class="card" data-enter>
        <p class="small muted" style="margin:0">
            Nobody has been selected yet. People appear here the moment you let
            them in from <a href="{{ route('admin.applications') }}">Applications</a>.
        </p>
    </div>
@else
    <div class="stack">
        @foreach ($rows as $row)
            @php($a = $row['application'])
            <div class="card {{ $row['attention'] ? 'card-raised' : 'card-quiet' }}" data-enter>
                <div class="row-between wrap" style="gap:var(--s-3);align-items:flex-start">
                    <div>
                        <p style="margin:0"><strong>{{ $a->name }}</strong></p>
                        <p class="small muted" style="margin:2px 0 0">{{ $a->email }}</p>
                    </div>

                    <span class="pill {{ $row['status'] === 'active' ? 'pill-manifested' : ($row['attention'] ? 'pill-unfolding' : '') }}">
                        {{ $row['label'] }}
                    </span>
                </div>

                <p class="small faint" style="margin:var(--s-3) 0 0">
                    Let in {{ $a->decided_at?->diffForHumans() ?? 'at some point' }}.
                    @if ($row['lastActive'])
                        Last here {{ $row['lastActive']->diffForHumans() }}.
                    @elseif ($row['stalled'] > 0)
                        {{ $row['stalled'] }} {{ Str::plural('day', $row['stalled']) }} with no movement.
                    @endif
                </p>

                <div class="row wrap" style="gap:var(--s-2);margin-top:var(--s-4)">
                    @if ($row['user'])
                        {{-- Straight to the account, where Suspend lives. --}}
                        <a class="btn btn-quiet btn-sm" href="{{ route('admin.users.show', $row['user']) }}">
                            Open their account
                        </a>
                    @endif

                    @if ($row['revocable'])
                        <form method="POST" action="{{ route('admin.testers.revoke', $a) }}"
                              data-confirm="Take back {{ $a->name }}'s seat? Their code stops working and they go back on the waitlist.">
                            @csrf
                            <button class="btn btn-quiet btn-sm" type="submit" style="color:var(--danger)">
                                Take the seat back
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p class="small faint center" style="margin-top:var(--s-6)">
        A seat can only be taken back from somebody who never signed up. Once
        there is an account, the tool is Suspend, on their own page.
        @unless (config('escalate.beta.notify_revoked'))
            Nobody is emailed when you take a seat back — that is off in
            <a href="{{ route('admin.settings.section', 'access') }}">Settings</a>.
        @endunless
    </p>
@endif
@endsection
