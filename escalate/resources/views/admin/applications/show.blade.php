@extends('layouts.app', ['title' => $application->name])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Application</p>
    <h1>{{ $application->name }}</h1>
    <p class="lede">{{ $application->email }}</p>
</div>

@include('admin._nav')

<div class="card" data-enter>
    <div class="row-between wrap" style="gap:var(--s-3)">
        <span class="pill">{{ $application->status }}</span>
        <span class="small faint">Applied {{ $application->created_at->diffForHumans() }}</span>
    </div>

    @if ($application->decided_at)
        <p class="small muted" style="margin:var(--s-4) 0 0">
            Decided {{ $application->decided_at->diffForHumans() }}
            @if ($application->decidedBy) by {{ $application->decidedBy->name }} @endif.
            @if ($application->invite)
                Their code is <strong>{{ $application->invite->code }}</strong>,
                {{ $application->invite->isClaimed() ? 'and they have used it.' : 'still unused.' }}
            @endif
        </p>
    @endif
</div>

<div class="rule">What they said</div>

@foreach ($application->answers() as $question => $answer)
    <div class="card card-quiet" data-enter>
        <p class="eyebrow">{{ $question }}</p>
        <p style="margin:var(--s-3) 0 0;white-space:pre-wrap">{{ $answer }}</p>
    </div>
@endforeach

@if ($application->isPending())
    <div class="rule">Decide</div>

    <div class="row wrap" style="gap:var(--s-3)">
        <form method="POST" action="{{ route('admin.applications.select', $application) }}"
              data-confirm="Give {{ $application->name }} a seat? This mints an invite and emails it to them.">
            @csrf
            <button class="btn" type="submit" data-busy="Selecting…">Select and send the code</button>
        </form>

        <form method="POST" action="{{ route('admin.applications.decline', $application) }}"
              data-confirm="Move {{ $application->name }} to the waitlist? Nothing is emailed.">
            @csrf
            <button class="btn btn-quiet" type="submit">Not this round</button>
        </form>
    </div>

    <p class="small faint" style="margin-top:var(--s-4)">
        The waitlist sends nothing. These are the people to write to when the
        public launch opens.
    </p>
@endif

<p class="small muted" style="margin-top:var(--s-7)">
    <a class="link-back" href="{{ route('admin.applications') }}">All applications</a>
</p>
@endsection
