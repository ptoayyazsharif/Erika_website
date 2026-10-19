@extends('layouts.app', ['title' => 'Feedback'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>What they said</h1>
    <p class="lede">
        The day-seven survey. These answers were written to you, which is why
        this screen may show them — no other admin screen shows anything anyone
        wrote.
    </p>
</div>

@include('admin._nav')

<div class="card {{ $score !== null && $score >= $bar ? 'card-raised' : '' }}" data-enter>
    <p class="eyebrow">Would be very disappointed to lose it</p>

    @if ($score === null)
        <p style="margin:var(--s-3) 0 0">No answers yet.</p>
    @else
        <div class="row wrap" style="gap:var(--s-6);margin-top:var(--s-3);align-items:baseline">
            <span class="serif" style="font-size:var(--t-3xl);line-height:1">{{ $score }}%</span>
            <span class="small muted">
                {{ $answered }} of {{ $eligible }} {{ Str::plural('tester', $eligible) }} answered
            </span>
        </div>

        <p class="small {{ $score >= $bar ? '' : 'muted' }}" style="margin:var(--s-4) 0 0">
            @if ($score >= $bar)
                Above the {{ $bar }}% line — this is the range where people miss a
                product when it goes away.
            @else
                Below the {{ $bar }}% line. That line is a rule of thumb, not a
                verdict, and it means more once most people have answered.
            @endif
        </p>
    @endif

    @if ($answered)
        <table style="width:100%;margin-top:var(--s-5);border-collapse:collapse">
            @foreach ($breakdown as $option)
                <tr>
                    <td class="small" style="padding:6px 0">{{ $option['label'] }}</td>
                    <td class="small faint" style="text-align:right;padding:6px 0">{{ $option['count'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif
</div>

@forelse ($responses as $response)
    <div class="card card-quiet" data-enter>
        <div class="row-between wrap" style="gap:var(--s-3)">
            <a href="{{ route('admin.users.show', $response->user) }}">
                <strong>{{ $response->user?->name ?? 'a deleted account' }}</strong>
            </a>
            <span class="pill">{{ $response->feeling() }}</span>
        </div>

        @foreach ([
            'Who they think it is for' => $response->who_for,
            'The main benefit'         => $response->benefit,
            'What to improve'          => $response->improve,
        ] as $question => $answer)
            @if (filled($answer))
                <p class="eyebrow" style="margin-top:var(--s-4)">{{ $question }}</p>
                <p style="margin:var(--s-2) 0 0;white-space:pre-wrap">{{ $answer }}</p>
            @endif
        @endforeach

        <p class="small faint" style="margin:var(--s-4) 0 0">{{ $response->created_at->diffForHumans() }}</p>
    </div>
@empty
    <div class="card" data-enter>
        <p class="small muted" style="margin:0">
            Nothing yet. Testers are asked once they have had the app for
            {{ \App\Http\Controllers\FeedbackController::AFTER_DAYS }} days.
        </p>
    </div>
@endforelse
@endsection
