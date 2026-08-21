@extends('layouts.app', ['title' => 'My Stories'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">My Stories</p>
    <h1>Everything written for you.</h1>
    <p class="lede">Readings are meant to be returned to. The ones you keep coming back to are the ones that are working.</p>
</div>

@forelse ($stories as $story)
    <a class="card" href="{{ route('stories.show', $story) }}" data-enter>
        <div class="row-between" style="margin-bottom:var(--s-3)">
            <span class="small faint">{{ $story->created_at->format('j M Y') }}</span>
            <div class="row" style="gap:var(--s-2)">
                @if ($story->favourite)
                    <span class="pill" style="color:var(--brass);border-color:color-mix(in srgb, var(--brass) 40%, transparent)">Saved</span>
                @endif
                @unless ($story->isReady())
                    <span class="pill">{{ $story->isPending() ? 'Being written' : 'Unfinished' }}</span>
                @endunless
            </div>
        </div>

        @if ($story->desire)
            <p class="eyebrow" style="margin-bottom:var(--s-2)">{{ Str::limit($story->desire->title, 50) }}</p>
        @endif

        @if ($story->isReady())
            <p class="serif" style="margin:0;font-size:var(--t-md);line-height:1.7">
                {{ Str::limit($story->text(), 170) }}
            </p>
            <p class="small faint" style="margin:var(--s-4) 0 0">
                {{ $story->words() }} words
                @if ($story->isEdited()) · edited @endif
                @if ($story->play_count) · played {{ $story->play_count }}× @endif
            </p>
        @endif
    </a>
@empty
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'book', 'size' => 34])
        <h3>Nothing written yet</h3>
        <p>Name a desire and the first reading follows in about twenty seconds.</p>
        <a class="btn" href="{{ route('desires.create') }}">Name a desire</a>
    </div>
@endforelse

@include('partials.pager', ['paginator' => $stories])
@endsection
