@extends('layouts.app', ['title' => 'Beta'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>The beta</h1>
    <p class="lede">
        Whether people activated, came back, stayed with it, and finished the
        seven days. Counts only — nothing here opens anybody's journal.
    </p>
</div>

@include('admin._nav')

<div class="row wrap" style="gap:var(--s-2);margin-bottom:var(--s-5)">
    <a class="chip {{ $cohort !== 'all' ? 'is-on' : '' }}"
       href="{{ route('admin.beta') }}">Founding 25</a>
    <a class="chip {{ $cohort === 'all' ? 'is-on' : '' }}"
       href="{{ route('admin.beta', ['cohort' => 'all']) }}">Everyone</a>
</div>

@if ($rows->isEmpty())
    <div class="card" data-enter>
        <p class="small muted" style="margin:0">
            Nobody in this group yet. Testers appear here once they have used
            their invite.
        </p>
    </div>
@else
    <div class="card" data-enter>
        <p class="eyebrow">{{ $rows->count() }} {{ Str::plural('tester', $rows->count()) }}</p>

        <div class="row wrap" style="gap:var(--s-6);margin-top:var(--s-4)">
            @foreach ($measures as $name => $measure)
                <div>
                    <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $share($measure['key']) }}%</span>
                    <span class="small muted">{{ $name }}</span>
                    <p class="small faint" style="margin:2px 0 0;max-width:16ch">{{ $measure['blurb'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rule">Person by person</div>

    @foreach ($rows as $row)
        <div class="card {{ $row['completed'] ? 'card-raised' : 'card-quiet' }}" data-enter>
            <div class="row-between wrap" style="gap:var(--s-3)">
                <a href="{{ route('admin.users.show', $row['user']) }}">
                    <strong>{{ $row['user']->name }}</strong>
                </a>
                <span class="small faint">
                    {{ $row['days'] }} {{ Str::plural('day', $row['days']) }} here ·
                    joined {{ $row['joined']?->diffForHumans() }}
                </span>
            </div>

            <div class="row wrap" style="gap:var(--s-2);margin-top:var(--s-3)">
                @foreach ($measures as $name => $measure)
                    <span class="pill" @if (! $row[$measure['key']]) style="color:var(--text-faint)" @endif>
                        {{ $row[$measure['key']] ? '✓' : '·' }} {{ $name }}
                    </span>
                @endforeach
            </div>

            <p class="small faint" style="margin:var(--s-3) 0 0">
                {{ $row['counts']['stories'] }} {{ Str::plural('reading', $row['counts']['stories']) }} ·
                {{ $row['counts']['narrations'] }} {{ Str::plural('narration', $row['counts']['narrations']) }} ·
                {{ $row['counts']['gratitude'] }} gratitude ·
                {{ $row['counts']['rewinds'] }} {{ Str::plural('rewind', $row['counts']['rewinds']) }} ·
                {{ $row['counts']['cards'] }} {{ Str::plural('card', $row['counts']['cards']) }} ·
                {{ $row['days_in_first_week'] }}/{{ \App\Support\BetaMetrics::TEST_LENGTH }} days in their first week
            </p>
        </div>
    @endforeach
@endif
@endsection
