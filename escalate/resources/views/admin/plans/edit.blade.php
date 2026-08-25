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
            <h3 style="margin-bottom:var(--s-2)">What it costs</h3>
            <p class="small muted" style="margin-bottom:var(--s-5)">
                Set the amount here and the product and price are created in Stripe for
                you, in whichever mode you are in — currently <strong>{{ $mode }}</strong>.
                You do not need to open the Stripe dashboard.
            </p>

            <div class="row wrap" style="gap:var(--s-3)">
                <div class="field grow" style="min-width:10rem">
                    <label for="amount_major">Amount</label>
                    <span class="hint">Per {{ $plan->interval ?: 'billing period' }}. Leave blank to price this plan by hand instead.</span>
                    <input class="input" id="amount_major" name="amount_major" type="number"
                           step="0.01" min="0" max="99999" placeholder="12.00"
                           value="{{ old('amount_major', $plan->amount !== null ? number_format($plan->amount / 100, 2, '.', '') : '') }}">
                </div>

                <div class="field" style="min-width:7rem">
                    <label class="label" for="currency">Currency</label>
                    <select class="select" id="currency" name="currency">
                        @foreach (['usd' => 'USD $', 'gbp' => 'GBP £', 'eur' => 'EUR €'] as $c => $label)
                            <option value="{{ $c }}" @selected(old('currency', $plan->currency ?: config('escalate.billing.currency','usd')) === $c)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- A Stripe Price cannot be edited, so changing the amount makes a new
                 one and archives the old. Said here rather than discovered later,
                 because the consequence — existing subscribers keep their old price —
                 is the behaviour you want but not the one people expect. --}}
            @if ($plan->exists && filled($plan->amount))
                <p class="small faint" style="margin:0 0 var(--s-4)">
                    Changing this creates a new price in Stripe and archives the current
                    one. Anyone already subscribed keeps paying what they agreed to until
                    you move them deliberately.
                </p>
            @endif

            <div class="rule">What Stripe has</div>

            <table style="width:100%;border-collapse:collapse">
                @foreach ([['live', $plan->stripe_price, $plan->stripe_product], ['test', $plan->stripe_price_test, $plan->stripe_product_test]] as [$m, $price, $product])
                    <tr>
                        <td class="small" style="padding:6px 0;vertical-align:top">
                            {{ ucfirst($m) }}
                            @if ($m === $mode)<span class="pill">in use</span>@endif
                        </td>
                        <td class="small faint" style="padding:6px 0;text-align:right;word-break:break-all">
                            {{ $price ?: 'not created yet' }}
                        </td>
                    </tr>
                @endforeach
            </table>

            <p class="small faint" style="margin:var(--s-3) 0 0">
                Created the first time you save with an amount and an interval set, using
                the keys for that mode. Switch mode and save again to create the other side.
            </p>
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
