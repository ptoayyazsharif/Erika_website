@extends('layouts.app', ['title' => $plan->exists ? $plan->label : 'New plan'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>{{ $plan->exists ? $plan->label : 'A new plan' }}</h1>
    <p class="lede">
        {{ $plan->exists
            ? 'Changes apply immediately to everyone on this plan.'
            : 'It appears in the picker as soon as it is active and has a price id for the current Stripe mode.' }}
    </p>
</div>

@include('admin._nav')

<form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" data-once>
    @csrf
    @if ($plan->exists) @method('PUT') @endif

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-5)">What it is</h3>

        <div class="field">
            <label for="key">Key</label>
            <span class="hint">
                Stored on every account that is put on this plan, so renaming it later
                is a data change rather than a rename. Lowercase letters, numbers,
                dashes and underscores.
                @if ($plan->isFree()) The free plan's key is fixed. @endif
            </span>
            <input class="input" id="key" name="key" type="text" required maxlength="40"
                   value="{{ old('key', $plan->key) }}" @if ($plan->isFree()) readonly @endif>
        </div>

        <div class="field">
            <label for="label">Name</label>
            <span class="hint">What people read on the plan page.</span>
            <input class="input" id="label" name="label" type="text" required maxlength="60"
                   value="{{ old('label', $plan->label) }}">
        </div>

        <div class="field">
            <label for="blurb">One line about it</label>
            <input class="input" id="blurb" name="blurb" type="text" maxlength="200"
                   value="{{ old('blurb', $plan->blurb) }}">
        </div>

        <div class="field">
            <label for="display">Price, as written</label>
            <span class="hint">
                Only a label. What the customer is actually charged lives in Stripe —
                if this disagrees with Stripe, this is the bug.
            </span>
            <input class="input" id="display" name="display" type="text" maxlength="60"
                   value="{{ old('display', $plan->display) }}" placeholder="$12 / month">
        </div>

        <div class="field" style="margin-bottom:0">
            <label class="label" for="interval">Billing interval</label>
            <select class="select" id="interval" name="interval">
                <option value="">None — the free plan</option>
                <option value="month" @selected(old('interval', $plan->interval) === 'month')>Monthly</option>
                <option value="year" @selected(old('interval', $plan->interval) === 'year')>Yearly</option>
            </select>
        </div>
    </div>

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-2)">What it allows, per day</h3>
        <p class="small muted" style="margin-bottom:var(--s-5)">
            This is the whole product difference between tiers. Applies only while
            billing is switched on; with it off everyone gets the flat limits in Settings.
        </p>

        @foreach ($kinds as $kind)
            <div class="field @if ($loop->last) style=margin-bottom:0 @endif">
                <label for="quota-{{ $kind }}">{{ ucfirst($kind === 'story' ? 'readings' : $kind.'s') }}</label>
                <input class="input" id="quota-{{ $kind }}" name="quotas[{{ $kind }}]"
                       type="number" min="0" max="1000"
                       value="{{ old('quotas.'.$kind, $plan->quota($kind)) }}">
            </div>
        @endforeach
    </div>

    @unless ($plan->isFree())
        <div class="card" data-enter>
            <h3 style="margin-bottom:var(--s-2)">Stripe price ids</h3>
            <p class="small muted" style="margin-bottom:var(--s-5)">
                Two, because Stripe's test and live worlds are entirely separate and a
                price id from one does not exist in the other. Whichever matches the
                current mode — <strong>{{ $mode }}</strong> — is the one used at checkout.
                The other is not wasted: it is what makes flipping the mode work.
            </p>

            <div class="field">
                <label for="stripe_price">Live price id {!! $mode === 'live' ? '<span class="pill">in use</span>' : '' !!}</label>
                <input class="input" id="stripe_price" name="stripe_price" type="text" maxlength="120"
                       autocomplete="off" spellcheck="false" placeholder="price_..."
                       value="{{ old('stripe_price', $plan->stripe_price) }}">
            </div>

            <div class="field" style="margin-bottom:0">
                <label for="stripe_price_test">Test price id {!! $mode === 'test' ? '<span class="pill">in use</span>' : '' !!}</label>
                <input class="input" id="stripe_price_test" name="stripe_price_test" type="text" maxlength="120"
                       autocomplete="off" spellcheck="false" placeholder="price_..."
                       value="{{ old('stripe_price_test', $plan->stripe_price_test) }}">
            </div>
        </div>
    @endunless

    <div class="card" data-enter>
        <div class="field">
            <label for="position">Order on the page</label>
            <span class="hint">Lower comes first.</span>
            <input class="input" id="position" name="position" type="number" min="0" max="999"
                   value="{{ old('position', $plan->position ?? 0) }}">
        </div>

        <label class="option {{ old('is_active', $plan->is_active ?? true) ? 'is-on' : '' }}" style="margin-bottom:0">
            <input type="checkbox" name="is_active" value="1"
                   @checked(old('is_active', $plan->is_active ?? true))
                   @if ($plan->isFree()) disabled @endif>
            <span class="tick" aria-hidden="true"></span>
            <span class="option-body">
                <span class="option-label">Offer this plan</span>
                <small>
                    Turning it off hides it from the picker. Anyone already on it keeps
                    it and keeps being charged — that is the point, and it is why
                    deactivating is the safe way to retire a tier rather than deleting.
                </small>
            </span>
        </label>
    </div>

    <div class="row wrap" style="gap:var(--s-3)">
        <button class="btn" type="submit" data-busy="Saving…">{{ $plan->exists ? 'Save plan' : 'Create plan' }}</button>
        <a class="btn btn-quiet" href="{{ route('admin.plans') }}">Cancel</a>
    </div>
</form>
@endsection
