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
{{-- auth-page, not a bare body: the desktop sidebar grid used to catch these
     pages too and lay the form into a 236px column. --}}
<body class="auth-page" @if (session('status')) data-flash="{{ session('status') }}" @endif>

<main class="auth-main">
    <div class="auth-say" data-enter-hero>
        <p class="eyebrow">Escalate</p>
        <h1>@yield('heading')</h1>
        <p class="lede">@yield('sub')</p>

        {{-- Desktop only. On a phone the form should be the first thing under
             the thumb, so this sits in the column the form does not use. --}}
        <p class="auth-aside small muted">
            A private journal. You name what you want, and it comes back to you
            written as an ordinary moment inside the life where you already have it.
        </p>
    </div>

    <div class="auth-do">

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

    {{-- This line used to read "Everything you write here is encrypted before it
         is stored." True, and materially misleading: a reader takes it to mean
         nobody can read it, while the app sends their words to two AI companies
         and holds the key itself. --}}
    <p class="small muted center" style="margin-top:var(--s-6);line-height:1.6">
        <span class="row" style="justify-content:center;gap:var(--s-2)">
            @include('partials.icon', ['name' => 'lock', 'size' => 14])
            <span>Encrypted before it is stored.</span>
        </span>
        <a href="{{ route('privacy') }}">What happens to what you write &rarr;</a>
    </p>
    </div>
</main>

</body>
</html>
