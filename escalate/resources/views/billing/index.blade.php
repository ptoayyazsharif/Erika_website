@extends('layouts.app', ['title' => 'Your plan'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Your plan</p>
    <h1>{{ $current === 'free' ? 'You are on the free plan.' : 'Thank you.' }}</h1>
    <p class="lede">
        {{ $current === 'free'
            ? 'Enough to see whether this is for you. A full plan is more of the same thing, not a different app — every feature is already open to you.'
            : 'Everything is open. Change or cancel whenever you like; nothing here locks you in.' }}
    </p>
</div>

@if (! config('escalate.billing.enabled'))
    <div class="notice notice-warn" role="status" data-enter>
        @include('partials.icon', ['name' => 'info', 'size' => 18])
        <div>
            Billing is switched off on this installation, so everyone has the
            full daily allowance and nothing can be bought. This page is here so
            you can see what it will look like.
        </div>
    </div>
@endif

{{-- What the current plan actually buys, in numbers. A plan page that only
     lists adjectives makes people work out what they are paying for. --}}
<div class="card" data-enter>
    <p class="eyebrow">Left today</p>
    <div class="row wrap" style="gap:var(--s-5);margin-top:var(--s-3)">
        {{-- Pluralised, because the free plan's allowance is 1 and "1 readings"
             is the first thing a new person reads on this page. --}}
        @foreach ([['story','reading'],['narration','narration'],['rewind','rewind']] as [$kind, $noun])
            <div>
                <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $remaining[$kind] }}</span>
                <span class="small muted">{{ Str::plural($noun, $remaining[$kind]) }}</span>
            </div>
        @endforeach
    </div>
    <p class="small faint" style="margin:var(--s-4) 0 0">
        Allowances reset on a rolling 24 hours, not at midnight.
    </p>
</div>

{{-- When the next charge falls, which is the question anybody on a paid plan
     opens this page to answer. Read from our own column: no Stripe call, so
     it still draws when Stripe is down. --}}
@if ($subscription && ! $subscription->onGracePeriod() && $current !== 'free')
    <div class="card card-quiet" data-enter>
        <p class="eyebrow">Your subscription</p>
        <p style="margin:var(--s-2) 0 0">
            <strong>{{ \App\Support\Plan::config($current)['label'] ?? 'Paid' }}</strong>
            @if ($priceLabel = \App\Support\Plan::config($current)['display'] ?? null)
                <span class="muted"> — {{ $priceLabel }}</span>
            @endif
        </p>
        <p class="small muted" style="margin:var(--s-2) 0 0">
            @if ($subscription->scheduled_price && $scheduledPlan)
                Changes to <strong>{{ $scheduledPlan['label'] }}</strong>
                @if ($subscription->current_period_end)
                    on {{ $subscription->current_period_end->format('j F Y') }}
                @else
                    when this period ends
                @endif.
                Nothing more is charged before then, and nothing you have changes.
            @elseif ($subscription->onTrial())
                Free until {{ $subscription->trial_ends_at->format('j F Y') }}, then it renews.
            @elseif ($subscription->current_period_end)
                Renews automatically on {{ $subscription->current_period_end->format('j F Y') }}.
            @else
                Renews automatically. The exact date appears after the next update from Stripe.
            @endif
        </p>

        @if ($subscription->scheduled_price)
            <form method="POST" action="{{ route('billing.keep') }}" data-once style="margin-top:var(--s-4)">
                @csrf
                <button class="btn btn-quiet btn-sm" type="submit" data-busy="Keeping…">
                    Keep my current plan instead
                </button>
            </form>
        @endif
    </div>
@endif

@if ($subscription?->onGracePeriod())
    <div class="notice notice-warn" role="status" data-enter>
        @include('partials.icon', ['name' => 'timer', 'size' => 18])
        <div>
            Your plan is cancelled and stays open until
            {{ $subscription->ends_at->format('j F Y') }}. Nothing more will be
            charged. You keep everything you have written either way.
        </div>
    </div>
@elseif ($subscription?->onTrial())
    <div class="notice" role="status" data-enter>
        @include('partials.icon', ['name' => 'info', 'size' => 18])
        <div>Your trial runs until {{ $subscription->trial_ends_at->format('j F Y') }}. You will not be charged before then.</div>
    </div>
@endif

@if ($user->cohort)
    <div class="card card-quiet" data-enter>
        <div class="row wrap" style="gap:var(--s-3);align-items:center">
            @include('partials.icon', ['name' => 'sparkle', 'size' => 18])
            <div>
                <p style="margin:0"><span class="pill pill-founding">{{ $user->cohort }}</span></p>
                <p class="small muted" style="margin:var(--s-1) 0 0">
                    You were here first. Your plan stays as it is, for as long
                    as you want it — there is nothing to pay.
                </p>
            </div>
        </div>
    </div>
@endif

<div class="rule">Plans</div>

{{-- Free is shown as a plan rather than an absence, so the comparison is
     honest about what someone already has. --}}
<div class="card {{ $current === 'free' ? 'card-raised' : 'card-quiet' }}" data-enter>
    <div class="row-between" style="margin-bottom:var(--s-2)">
        <h3 style="margin:0">{{ $free['label'] }}</h3>
        <span class="pill">{{ $free['display'] }}</span>
    </div>
    <p class="small muted" style="margin:0 0 var(--s-3)">{{ $free['blurb'] }}</p>
    <p class="small faint" style="margin:0">
        {{ $free['quotas']['story'] }} {{ Str::plural('reading', $free['quotas']['story']) }},
        {{ $free['quotas']['narration'] }} {{ Str::plural('narration', $free['quotas']['narration']) }} and
        {{ $free['quotas']['rewind'] }} {{ Str::plural('rewind', $free['quotas']['rewind']) }} a day.
        @if ($current === 'free') <strong>Your plan.</strong> @endif
    </p>
</div>

@forelse ($plans as $key => $plan)
    <div class="card {{ $current === $key ? 'card-raised' : '' }}" data-enter>
        <div class="row-between" style="margin-bottom:var(--s-2)">
            <h3 style="margin:0">{{ $plan['label'] }}</h3>
            <span class="pill">{{ $plan['display'] }}</span>
        </div>
        <p class="small muted" style="margin:0 0 var(--s-3)">{{ $plan['blurb'] }}</p>
        <p class="small faint" style="margin:0 0 var(--s-4)">
            {{ $plan['quotas']['story'] }} {{ Str::plural('reading', $plan['quotas']['story']) }},
            {{ $plan['quotas']['narration'] }} {{ Str::plural('narration', $plan['quotas']['narration']) }} and
            {{ $plan['quotas']['rewind'] }} {{ Str::plural('rewind', $plan['quotas']['rewind']) }} a day.
        </p>

        @if ($current === $key)
            <p class="small" style="margin:0"><strong>Your plan.</strong></p>
        @else
            <form method="POST" action="{{ route('billing.checkout') }}" data-once>
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                <button class="btn btn-full" type="submit" data-busy="Taking you to Stripe…"
                        @unless (config('escalate.billing.enabled')) aria-disabled="true" disabled @endunless>
                    {{ \App\Support\PlanChange::label($user, $key) }}
                </button>
            </form>
        @endif
    </div>
@empty
    <div class="empty" data-enter>
        @include('partials.icon', ['name' => 'info', 'size' => 34])
        <h3>No plans are configured</h3>
        <p>
            A plan needs a Stripe price id before it can be offered. Set
            <code>STRIPE_PRICE_MONTHLY</code> and <code>STRIPE_PRICE_YEARLY</code>,
            and they appear here.
        </p>
    </div>
@endforelse

@if ($user->hasStripeId())
    <div class="rule">Payment</div>
    <p class="small muted">
        Cards, invoices and cancelling all live with Stripe — this app never
        sees a card number.
    </p>
    <a class="btn btn-ghost" href="{{ route('billing.portal') }}">
        @include('partials.icon', ['name' => 'lock', 'size' => 16])
        Manage payment and invoices
    </a>
@endif

<p class="small faint" style="margin-top:var(--s-7)">
    Paid plans renew automatically until you cancel, and cancelling takes effect
    at the end of the period you have already paid for.
    <a href="{{ route('privacy') }}">What happens to what you write &rarr;</a>
</p>
@endsection
