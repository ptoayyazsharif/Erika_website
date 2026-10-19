@extends('layouts.app', ['title' => 'Settings'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Settings</h1>
    <p class="lede">
        Everything here overrides what the server was deployed with and takes
        effect immediately — no redeploy. Clear a field to go back to the
        original value.
    </p>
</div>

@include('admin._nav')

<div class="stack" data-enter>
    @foreach ($sections as $key => $section)
        <a class="card" href="{{ route('admin.settings.section', $key) }}">
            <div class="row-between" style="gap:var(--s-3)">
                <h3 style="margin:0;font-size:var(--t-md)">{{ $section['label'] }}</h3>
                @include('partials.icon', ['name' => 'chevron-right', 'size' => 18])
            </div>
            <p class="small muted" style="margin:var(--s-2) 0 0">{{ $section['blurb'] }}</p>
        </a>
    @endforeach
</div>
@endsection
