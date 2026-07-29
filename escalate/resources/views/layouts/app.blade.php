<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#101521">
    <meta name="color-scheme" content="dark light">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · Escalate' : 'Escalate' }}</title>

    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/favicon-32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Escalate">

    <link rel="preload" href="/fonts/lora-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/raleway-var.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">

    <script src="{{ asset_v('js/gsap.min.js') }}" defer></script>
    <script src="{{ asset_v('js/app.js') }}" defer></script>
    @stack('head')
</head>
<body @if (session('status')) data-flash="{{ session('status') }}" @endif>

<header class="topbar">
    <a class="brand" href="{{ route('today') }}">Escalat<span>e</span></a>
    <div class="grow"></div>

    <button type="button" class="btn btn-icon" data-install hidden
            title="Install Escalate on this device">
        @include('partials.icon', ['name' => 'download', 'size' => 19])
        <span class="sr-only">Install Escalate</span>
    </button>

    <button type="button" class="btn btn-icon" data-theme-toggle aria-pressed="false">
        @include('partials.icon', ['name' => 'moon', 'size' => 19])
    </button>
</header>

@auth
    <nav class="tabbar" aria-label="Sections">
        @php
            // Short label for the mobile tab bar, full label for the desktop
            // sidebar and for screen readers. "My Stories" on a 60px tab wraps
            // to two lines and drags the icon out of alignment with its
            // neighbours, so the tab bar gets the one-word form.
            $nav = [
                ['today',          'Today',      'Today',      'sunrise'],
                ['stories.index',  'My Stories', 'Stories',    'book'],
                ['desires.index',  'Desires',    'Desires',    'compass'],
                ['gratitude.index','Gratitude',  'Gratitude',  'heart'],
                ['rewinds.index',  'My Rewinds', 'Rewinds',    'rewind'],
                ['journey',        'My Journey', 'Journey',    'journey'],
                ['world.edit',     'My World',   'World',      'world'],
            ];
        @endphp
        @foreach ($nav as [$route, $label, $short, $icon])
            <a href="{{ route($route) }}" aria-label="{{ $label }}"
               @if (request()->routeIs(Str::before($route, '.').'*')) aria-current="page" @endif>
                @include('partials.icon', ['name' => $icon, 'size' => 21])
                <span class="tab-short">{{ $short }}</span>
                <span class="tab-full">{{ $label }}</span>
            </a>
        @endforeach
    </nav>
@endauth

<main class="shell" id="main">
    @if ($errors->any() && ! isset($suppressErrorSummary))
        <div class="notice notice-error" role="alert">
            @include('partials.icon', ['name' => 'alert', 'size' => 18])
            <div>
                @if ($errors->count() === 1)
                    {{ $errors->first() }}
                @else
                    <strong>A few things need another look.</strong>
                    <ul style="margin:6px 0 0;padding-left:18px">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    @yield('content')
</main>

@stack('scripts')
</body>
</html>
