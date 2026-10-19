@extends('layouts.app', ['title' => $person->name])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>{{ $person->name }}</h1>
    <p class="small muted">{{ $person->email }}</p>
    <p class="small faint">
        Joined {{ $person->created_at->format('j F Y') }} ·
        {{ $person->last_login_at ? 'last seen '.$person->last_login_at->diffForHumans() : 'never signed in' }}
        @if ($person->isAdmin()) · administrator @endif
    </p>
</div>

@include('admin._nav')

@if ($person->suspended_at)
    <div class="notice notice-warn" role="status" data-enter>
        @include('partials.icon', ['name' => 'alert', 'size' => 18])
        <div>Suspended {{ $person->suspended_at->diffForHumans() }}. They are signed out on their next request and cannot sign back in.</div>
    </div>
@endif

<div class="card" data-enter>
    @if ($person->cohort)
        <p class="small" style="margin:0 0 var(--s-4)">
            <span class="pill pill-founding">{{ $person->cohort }}</span>
        </p>
    @endif

    <p class="eyebrow">What they have</p>
    <div class="row wrap" style="gap:var(--s-6);margin-top:var(--s-3)">
        @foreach ([['desires','desires'],['stories','readings'],['gratitude','gratitude entries'],['rewinds','rewinds']] as [$k,$label])
            <div>
                <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $counts[$k] }}</span>
                <span class="small muted">{{ $label }}</span>
            </div>
        @endforeach
    </div>
    <p class="small faint" style="margin:var(--s-4) 0 0">
        Counts only. Everything they wrote is encrypted and is not readable from
        here — that is what the privacy disclosure promises them.
    </p>
</div>

<div class="card" data-enter>
    <p class="eyebrow">Today's usage</p>
    <table style="width:100%;margin-top:var(--s-3);border-collapse:collapse">
        @foreach ($usage as $kind => $u)
            <tr>
                <td class="small" style="padding:6px 0">{{ Str::plural(ucfirst($kind), 2) }}</td>
                <td class="small faint" style="text-align:right;padding:6px 0">{{ $u['used'] }} / {{ $u['limit'] }}</td>
            </tr>
        @endforeach
    </table>
</div>

<div class="rule">Plan</div>

<form method="POST" action="{{ route('admin.users.plan', $person) }}" class="card" data-enter>
    @csrf
    @method('PATCH')

    <p class="small muted" style="margin-bottom:var(--s-4)">
        Currently on <strong>{{ $plan }}</strong>{{ $person->plan_override ? ' by hand' : ' from their subscription' }}.
        Setting this does not touch Stripe and does not charge anyone — it is how
        somebody is comped.
    </p>

    <div class="field">
        <label class="label" for="plan">Put them on</label>
        <select class="select" id="plan" name="plan">
            <option value="">No override — follow their subscription</option>
            @foreach ($plans as $key => $p)
                <option value="{{ $key }}" @selected($person->plan_override === $key)>{{ $p['label'] }} ({{ $key }})</option>
            @endforeach
        </select>
    </div>

    <button class="btn btn-ghost" type="submit">Save plan</button>
</form>

@unless ($person->id === auth()->id())
    <div class="rule">Access</div>

    <form method="POST" action="{{ route('admin.users.suspend', $person) }}"
          data-confirm="{{ $person->suspended_at ? 'Restore this account?' : 'Suspend this account? They are signed out on their next request.' }}">
        @csrf
        <button class="btn btn-quiet" type="submit" @unless ($person->suspended_at) style="color:var(--danger)" @endunless>
            {{ $person->suspended_at ? 'Restore access' : 'Suspend this account' }}
        </button>
    </form>
@endunless

<p class="small muted" style="margin-top:var(--s-6)">
    <a class="link-back" href="{{ route('admin.users') }}">Back to people</a>
</p>
@endsection
