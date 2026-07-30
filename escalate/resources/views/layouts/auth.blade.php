<!DOCTYPE html>
<html lang="en" data-theme="{{ active_theme() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ theme_meta()['chrome'] }}">
    <meta name="color-scheme" content="{{ theme_meta()['scheme'] }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ isset($title) ? $title.' · Escalate' : 'Escalate' }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/favicon-32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

    <link rel="preload" href="/fonts/lora-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/raleway-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">

    <script src="{{ asset_v('js/gsap.min.js') }}" defer></script>
    <script src="{{ asset_v('js/app.js') }}" defer></script>
</head>
<body @if (session('status')) data-flash="{{ session('status') }}" @endif
      style="display:grid;place-items:center;padding:var(--s-5) var(--s-4)">

<main style="width:100%;max-width:26rem">
    <div data-enter-hero>
        <p class="eyebrow">Escalate</p>
        <h1 style="margin-bottom:var(--s-3)">@yield('heading')</h1>
        <p class="lede" style="margin-bottom:var(--s-6)">@yield('sub')</p>
    </div>

    @if ($errors->any())
        <div class="notice notice-error" role="alert">
            @include('partials.icon', ['name' => 'alert', 'size' => 18])
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    @if (session('status'))
        <div class="notice notice-ok" role="status">
            @include('partials.icon', ['name' => 'info', 'size' => 18])
            <div>{{ session('status') }}</div>
        </div>
    @endif

    @yield('form')

    <p class="small muted row" style="margin-top:var(--s-6);justify-content:center;gap:var(--s-2)">
        @include('partials.icon', ['name' => 'lock', 'size' => 14])
        <span>Everything you write here is encrypted before it is stored.</span>
    </p>
</main>

</body>
</html>
