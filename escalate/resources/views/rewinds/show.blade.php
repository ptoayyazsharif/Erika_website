@extends('layouts.app', ['title' => 'A Rewind'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">{{ $rewind->desire?->statusLabel() }}</p>
    <h1>{{ $rewind->desire?->title ?? 'A Rewind' }}</h1>
    <p class="lede">
        Named {{ $rewind->desire?->created_at->format('j F Y') }}@if ($rewind->desire?->status_changed_at), finished {{ $rewind->desire->status_changed_at->format('j F Y') }}@endif.
    </p>
</div>

{{-- The written retrospective, when there is one. It comes first because once
     it exists it is the thing people come back for. --}}
@if ($rewind->isReady())
    <article class="reading" data-enter>
        @foreach (preg_split('/\n{2,}/', trim($rewind->narrative)) as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </article>
@elseif ($rewind->isPending())
    <div class="notice" data-enter>
        @include('partials.icon', ['name' => 'timer', 'size' => 18])
        <div>Being written. This page will have it when you come back.</div>
    </div>
@elseif ($rewind->state === 'failed')
    <div class="notice notice-warn" data-enter>
        @include('partials.icon', ['name' => 'alert', 'size' => 18])
        <div>{{ $rewind->failure_reason }}</div>
    </div>
@endif

{{-- Their own answers, always shown. These are the part they wrote, and they
     stay readable whether or not anything was ever generated from them. --}}
@if ($rewind->answered())
    <div class="card" data-enter style="margin-top:var(--s-6)">
        <h3 style="margin-bottom:var(--s-5)">What you wrote</h3>

        @foreach ($rewind->answered() as $row)
            <div style="margin-bottom:var(--s-5)">
                <p class="eyebrow" style="margin-bottom:var(--s-2)">{{ $row['label'] }}</p>
                <p class="muted" style="margin:0;white-space:pre-line">{{ $row['value'] }}</p>
            </div>
        @endforeach

        <a class="btn btn-ghost btn-sm" href="{{ route('rewinds.create', $rewind->desire) }}">Edit your answers</a>
    </div>
@endif

<div style="margin-top:var(--s-6)" data-enter>
    <form method="POST" action="{{ route('rewinds.generate', $rewind) }}" data-once>
        @csrf
        <button class="btn btn-full" type="submit" data-busy="Writing…"
                @if ($rewind->isPending() || $remaining < 1) aria-disabled="true" disabled @endif>
            @include('partials.icon', ['name' => 'sparkle', 'size' => 16])
            {{ $rewind->isReady() ? 'Write it again' : 'Write it up' }}
        </button>
    </form>

    <p class="small faint center" style="margin-top:var(--s-3)">
        {{ $remaining }} {{ Str::plural('rewind', $remaining) }} left today ·
        written only from your answers, never from anything invented
    </p>
</div>

{{-- data-confirm belongs on the form element: initConfirm() listens for submit
     and reads the attribute off the form. It sat on the button here, where
     nothing read it, so the one irreversible action on this screen — deleting a
     Rewind and every answer typed into it — fired on a single click with no
     question asked. --}}
<form method="POST" action="{{ route('rewinds.destroy', $rewind) }}" style="margin-top:var(--s-6)"
      data-confirm="Delete this Rewind and everything you wrote in it? This cannot be undone.">
    @csrf
    @method('DELETE')
    <button class="btn btn-quiet btn-sm" type="submit">
        Delete this Rewind
    </button>
</form>

<a class="link-back" href="{{ route('rewinds.index') }}">Back to My Rewinds</a>
@endsection
