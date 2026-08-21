@extends('layouts.app', ['title' => 'Plans'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Plans</h1>
    <p class="lede">
        What people can be on, and what each one allows per day. Changes apply
        immediately — no redeploy.
    </p>
</div>

@include('admin._nav')

<div class="notice {{ $mode === 'test' ? 'notice-warn' : '' }}" role="status" data-enter>
    @include('partials.icon', ['name' => $mode === 'test' ? 'alert' : 'lock', 'size' => 18])
    <div>
        Stripe is in <strong>{{ $mode }}</strong> mode, so the
        <strong>{{ $mode }} price id</strong> on each plan is the one being used.
        Stripe keeps the two worlds entirely separate — an id from one is
        meaningless in the other — which is why each plan carries both.
        <a href="{{ route('admin.settings') }}">Change the mode &rarr;</a>
    </div>
</div>

<div class="row wrap" style="margin-bottom:var(--s-5)">
    <a class="btn btn-sm" href="{{ route('admin.plans.create') }}">
        @include('partials.icon', ['name' => 'plus', 'size' => 16]) New plan
    </a>
</div>

@foreach ($plans as $plan)
    @php $price = $mode === 'test' ? $plan->stripe_price_test : $plan->stripe_price; @endphp
    <div class="card {{ $plan->is_active ? '' : 'card-quiet' }}" data-enter>
        <div class="row-between wrap" style="gap:var(--s-3);margin-bottom:var(--s-2)">
            <div>
                <h3 style="margin:0">{{ $plan->label }}</h3>
                <p class="small faint" style="margin:4px 0 0">
                    <code>{{ $plan->key }}</code>
                    @if ($plan->interval) · per {{ $plan->interval }} @endif
                    @if (($counts[$plan->key] ?? 0) > 0)
                        · {{ $counts[$plan->key] }} {{ Str::plural('person', $counts[$plan->key]) }} on it
                    @endif
                </p>
            </div>
            <div class="row" style="gap:var(--s-2)">
                @unless ($plan->is_active)<span class="pill">inactive</span>@endunless
                <span class="pill">{{ $plan->display ?: '—' }}</span>
            </div>
        </div>

        @if ($plan->blurb)
            <p class="small muted" style="margin:0 0 var(--s-3)">{{ $plan->blurb }}</p>
        @endif

        <p class="small faint" style="margin:0 0 var(--s-4)">
            @foreach ($kinds as $kind)
                {{ $plan->quota($kind) }} {{ Str::plural($kind === 'story' ? 'reading' : $kind, $plan->quota($kind)) }}@if (! $loop->last), @endif
            @endforeach
            a day
        </p>

        {{-- The price id for the ACTIVE mode is what decides whether this plan
             can be bought right now, so it is what gets called out. --}}
        @unless ($plan->isFree())
            @if (blank($price))
                <p class="small" style="margin:0 0 var(--s-4);color:var(--danger)">
                    No {{ $mode }} price id — this plan is hidden from the picker until one is set.
                </p>
            @else
                <p class="small faint" style="margin:0 0 var(--s-4)"><code>{{ $price }}</code></p>
            @endif
        @endunless

        <div class="row wrap" style="gap:var(--s-3)">
            <a class="btn btn-quiet btn-sm" href="{{ route('admin.plans.edit', $plan) }}">
                @include('partials.icon', ['name' => 'edit', 'size' => 15]) Edit
            </a>

            @unless ($plan->isFree())
                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                      data-confirm="Delete “{{ $plan->label }}”? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-quiet btn-sm" type="submit" style="color:var(--danger)">Delete</button>
                </form>
            @endunless
        </div>
    </div>
@endforeach
@endsection
