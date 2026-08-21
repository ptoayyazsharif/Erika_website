@extends('layouts.app', ['title' => 'Admin'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Overview</h1>
    <p class="lede">
        What is happening and what it is costing. Nothing on this screen reads
        anybody's journal — the numbers come from the spend ledger and from row
        counts, and every entry stays encrypted.
    </p>
</div>

@include('admin._nav')

<div class="card" data-enter>
    <p class="eyebrow">People</p>
    <div class="row wrap" style="gap:var(--s-6);margin-top:var(--s-3)">
        @foreach ([['total','total'],['active','active this week'],['suspended','suspended'],['comped','on a manual plan']] as [$k,$label])
            <div>
                <span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $people[$k] }}</span>
                <span class="small muted">{{ $label }}</span>
            </div>
        @endforeach
    </div>
</div>

<div class="card" data-enter>
    <p class="eyebrow">Invites</p>
    <div class="row wrap" style="gap:var(--s-6);margin-top:var(--s-3)">
        <div><span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $invites['open'] }}</span> <span class="small muted">still open</span></div>
        <div><span class="serif" style="font-size:var(--t-2xl);line-height:1">{{ $invites['claimed'] }}</span> <span class="small muted">used</span></div>
    </div>
    <a class="btn btn-quiet btn-sm" href="{{ route('admin.invites') }}" style="margin-top:var(--s-4)">Hand some out</a>
</div>

{{-- The ceiling, which is the number that stops a bad day becoming a bad bill. --}}
<div class="card {{ collect($ceilings)->contains(fn ($c) => $c['limit'] > 0 && $c['used'] >= $c['limit']) ? 'card-raised' : '' }}" data-enter>
    <p class="eyebrow">Today, against the ceiling</p>
    <table style="width:100%;margin-top:var(--s-3);border-collapse:collapse">
        @foreach ($ceilings as $kind => $c)
            <tr>
                <td class="small" style="padding:6px 0">{{ Str::plural(ucfirst($kind), 2) }}</td>
                <td class="small" style="text-align:right;padding:6px 0">
                    {{ $c['used'] }} / {{ $c['limit'] > 0 ? $c['limit'] : '∞' }}
                    @if ($c['limit'] > 0 && $c['used'] >= $c['limit'])
                        <span class="pill" style="color:var(--danger)">reached</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    <p class="small faint" style="margin:var(--s-4) 0 0">
        Rolling 24 hours, across every account. A limit of ∞ means that ceiling is switched off.
    </p>
</div>

<div class="rule">Spend</div>

<div class="card" data-enter>
    <p class="eyebrow">Last 24 hours</p>
    <p class="small muted" style="margin:var(--s-2) 0 var(--s-4)">
        {{ $today['calls'] }} {{ Str::plural('call', $today['calls']) }},
        {{ $today['failed'] }} failed.
    </p>

    <p class="eyebrow">Last 30 days</p>
    <table style="width:100%;margin-top:var(--s-3);border-collapse:collapse">
        @forelse ($month['by_kind'] as $row)
            <tr>
                <td class="small" style="padding:6px 0">{{ ucfirst($row->kind) }}</td>
                <td class="small faint" style="text-align:right;padding:6px 0">
                    {{ $row->calls }} {{ Str::plural('call', $row->calls) }}
                </td>
            </tr>
        @empty
            <tr><td class="small muted" style="padding:6px 0">Nothing generated yet.</td></tr>
        @endforelse
    </table>
    <p class="small faint" style="margin:var(--s-4) 0 0">
        Call counts are exact and are what the ceiling is enforced on. The
        currency figures in the ledger are an estimate from a pricing table in
        the code — reconcile against the provider's own invoice, never against
        this.
    </p>
</div>

@if ($failures->isNotEmpty())
    <div class="card card-quiet" data-enter>
        <p class="eyebrow">Failures in the last day</p>
        <table style="width:100%;margin-top:var(--s-3);border-collapse:collapse">
            @foreach ($failures as $f)
                <tr>
                    <td class="small" style="padding:6px 0">{{ $f->error_code ?: 'unknown' }} <span class="faint">· {{ $f->kind }}</span></td>
                    <td class="small faint" style="text-align:right;padding:6px 0">{{ $f->total }}</td>
                </tr>
            @endforeach
        </table>
        <p class="small faint" style="margin:var(--s-4) 0 0">
            <code>quota_exceeded</code> on narration usually means the ElevenLabs
            key has its own credit cap, not that the account is empty.
        </p>
    </div>
@endif
@endsection
