@extends('layouts.app', ['title' => 'Invites'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Invites</h1>
    <p class="lede">
        Each code works once. The link prefills it, so an invitation is one tap
        for whoever receives it.
    </p>
</div>

@include('admin._nav')

@if (filled(config('escalate.copy.outreach')))
    {{-- The message Erika sends people, kept where somebody already is when
         they need it. Editable in Settings → Words on the public pages. --}}
    <details class="card card-quiet" data-enter>
        <summary><span class="small">The message to send</span></summary>
        <p style="margin:var(--s-3) 0 0;white-space:pre-wrap">{{ config('escalate.copy.outreach') }}</p>
    </details>
@endif

<form method="POST" action="{{ route('admin.invites.store') }}" class="card" data-once data-enter>
    @csrf
    <h3 style="margin-bottom:var(--s-5)">Mint some</h3>

    <div class="field">
        <label for="count">How many</label>
        <input class="input" id="count" name="count" type="number" min="1" max="50" value="1">
    </div>

    <div class="field">
        <label for="email">For one address only <span class="faint">(optional)</span></label>
        <span class="hint">Binds the code to this address, so forwarding it is useless. Leaving it blank makes a code anyone can use once.</span>
        <input class="input" id="email" name="email" type="email" autocomplete="off">
    </div>

    <div class="field">
        <label for="note">A note to yourself <span class="faint">(optional)</span></label>
        <span class="hint">Never shown to the person invited.</span>
        <input class="input" id="note" name="note" type="text" maxlength="120">
    </div>

    <div class="field">
        <label for="days">Expires after, in days</label>
        <span class="hint">0 for never.</span>
        <input class="input" id="days" name="days" type="number" min="0" max="3650"
               value="{{ config('escalate.beta.invite_days') }}">
    </div>

    <button class="btn" type="submit" data-busy="Minting…">Mint</button>
</form>

@if (! config('escalate.beta.invite_only'))
    <div class="notice notice-warn" role="status" data-enter>
        @include('partials.icon', ['name' => 'alert', 'size' => 18])
        <div>
            Registration is currently open to anyone — “Invite only” is switched
            off in <a href="{{ route('admin.settings') }}">Settings</a>. These
            codes will work, but nothing is requiring them.
        </div>
    </div>
@endif

<div class="rule">Handed out</div>

@forelse ($invites as $invite)
    <div class="card card-quiet" data-enter>
        <div class="row-between wrap" style="gap:var(--s-3)">
            <div>
                <p class="serif" style="margin:0;font-size:var(--t-md);letter-spacing:0.06em">{{ $invite->code }}</p>
                <p class="small faint" style="margin:4px 0 0">
                    @if ($invite->isClaimed())
                        Used {{ $invite->claimed_at->diffForHumans() }} by
                        {{ $invite->claimant?->email ?? 'an account since deleted' }}
                    @elseif ($invite->isExpired())
                        Expired {{ $invite->expires_at->diffForHumans() }}
                    @else
                        Open{{ $invite->expires_at ? ', until '.$invite->expires_at->format('j M Y') : '' }}
                        @if ($invite->email) · for {{ $invite->email }} @endif
                    @endif
                    @if ($invite->note) · {{ $invite->note }} @endif
                </p>
            </div>

            @unless ($invite->isClaimed())
                <form method="POST" action="{{ route('admin.invites.destroy', $invite) }}"
                      data-confirm="Withdraw this invite? Anyone holding the code will not be able to use it.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-quiet btn-sm" type="submit">Withdraw</button>
                </form>
            @endunless
        </div>
    </div>
@empty
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'plus', 'size' => 34])
        <h3>None yet</h3>
        <p>Mint a few above and send the links out.</p>
    </div>
@endforelse

@include('partials.pager', ['paginator' => $invites])
@endsection
