<!DOCTYPE html>
<html lang="en" data-theme="{{ active_theme() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ theme_meta()['chrome'] }}">
    <meta name="color-scheme" content="{{ theme_meta()['scheme'] }}">
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
{{-- app-shell marks the signed-in layout. The desktop sidebar grid keys off
     it, so the auth pages — which have no sidebar — cannot inherit it. --}}
<body class="app-shell" @if (session('status')) data-flash="{{ session('status') }}" @endif>

<header class="topbar">
    <a class="brand" href="{{ route('today') }}">
        @include('partials.mark', ['size' => 26])
        <span class="brand-word">Escalate</span>
    </a>
    <div class="grow"></div>

    {{-- The only way into the admin area used to be typing the URL: the admin
         nav only renders once you are already inside it. Labelled rather than
         another glyph, because the bar already has two of those and a third
         would not be findable.

         Rendered only for an admin — a link an ordinary user could see would
         undo the 404 that hides the admin area from everybody else. --}}
    @if (auth()->user()?->isAdmin())
        <a class="btn btn-quiet btn-sm" href="{{ route('admin.dashboard') }}">Admin</a>
    @endif

    <button type="button" class="btn btn-icon" data-install hidden
            title="Install Escalate on this device">
        @include('partials.icon', ['name' => 'download', 'size' => 19])
        <span class="sr-only">Install Escalate</span>
    </button>

    {{-- Quick light/dark switch. With six themes a plain toggle would be
         ambiguous, so each theme names its counterpart in config and this flips
         between the pair. Full selection lives in My World. --}}
    <button type="button" class="btn btn-icon"
            data-theme-toggle
            data-theme-current="{{ active_theme() }}"
            data-theme-counterpart="{{ theme_meta()['counterpart'] }}"
            data-theme-url="{{ route('world.theme') }}"
            aria-label="Switch to {{ theme_meta(theme_meta()['counterpart'])['label'] }}">
        @include('partials.icon', ['name' => theme_meta()['scheme'] === 'dark' ? 'sun' : 'moon', 'size' => 19])
        <span class="sr-only">Switch theme</span>
    </button>
</header>

@auth
    @php
        // The admin area gets its own navigation in the same bar.
        //
        // It used to render the seven customer sections — Today, My Stories,
        // Gratitude — while you were standing in Settings, which is both
        // confusing and wrong about where you are. Two different places, two
        // different sets of destinations, one component.
        //
        // Short label for the mobile tab bar, full label for the desktop
        // sidebar and for screen readers. "My Stories" on a 60px tab wraps to
        // two lines and drags the icon out of alignment with its neighbours,
        // so the tab bar gets the one-word form.
        $inAdmin = request()->routeIs('admin.*');

        $nav = $inAdmin ? [
            ['admin.dashboard', 'Overview', 'Overview', 'journey'],
            ['admin.users',     'People',   'People',   'compass'],
            ['admin.plans',     'Plans',    'Plans',    'book'],
            ['admin.beta',      'Beta',     'Beta',     'sparkle'],
            ['admin.feedback',  'Said',     'Feedback', 'book'],
            ['admin.applications', 'Apply', 'Applications', 'heart'],
            ['admin.testers',   'Testers',  'Testers',  'timer'],
            ['admin.announcements', 'Say',  'Announcements', 'quote'],
            ['admin.invites',   'Invites',  'Invites',  'plus'],
            ['admin.settings',  'Settings', 'Settings', 'world'],
            ['today',           'Back to the app', 'Exit', 'sunrise'],
        ] : [
            ['today',          'Today',      'Today',      'sunrise'],
            ['affirmations',   'My Cards',   'Cards',      'sparkle'],
            ['stories.index',  'My Stories', 'Stories',    'book'],
            ['desires.index',  'Desires',    'Desires',    'compass'],
            ['gratitude.index','Gratitude',  'Gratitude',  'heart'],
            ['rewinds.index',  'My Rewinds', 'Rewinds',    'rewind'],
            ['journey',        'My Journey', 'Journey',    'journey'],
            ['world.edit',     'My World',   'World',      'world'],
        ];
    @endphp

    <nav class="tabbar" aria-label="{{ $inAdmin ? 'Admin' : 'Sections' }}">
        @foreach ($nav as [$route, $label, $short, $icon])
            @php
                // 'admin.users' must not light up for 'admin.dashboard', so the
                // admin entries match on their own full name rather than on the
                // first segment the way the customer ones do.
                $current = $inAdmin
                    ? (request()->routeIs($route) || request()->routeIs($route.'.*'))
                    : request()->routeIs(Str::before($route, '.').'*');
            @endphp
            <a href="{{ route($route) }}" aria-label="{{ $label }}"
               @if ($current) aria-current="page" @endif>
                @include('partials.icon', ['name' => $icon, 'size' => 21])
                <span class="tab-short">{{ $short }}</span>
                <span class="tab-full">{{ $label }}</span>
            </a>
        @endforeach
    </nav>
@endauth

<main class="shell" id="main">
@auth
    @include('partials.announcement-banner')
@endauth

{{-- The install nudge.

     The topbar button alone only ever appears when `beforeinstallprompt`
     fires, which is Chrome and Android. iOS Safari never fires it, so on an
     iPhone there was no way to discover the app is installable at all — and for
     a beta handed round by DM that is most of the audience.

     Shown once, dismissible, and never in an already-installed window. The
     wording is filled in by app.js because it differs by platform: Android gets
     a button that opens the real prompt, iOS gets the only instruction that
     works there. --}}
<div class="install-tip" data-install-tip hidden role="status" aria-live="polite">
    <p class="install-tip-text" data-install-tip-text></p>
    <div class="install-tip-actions">
        <button type="button" class="btn btn-sm" data-install-tip-go hidden>Add it</button>
        <button type="button" class="btn btn-quiet btn-sm" data-install-tip-close>Not now</button>
    </div>
</div>

    {{-- Unconfirmed email. Shown rather than enforced: the person can use the
         whole app except the four things that cost money, so this is a nudge
         and not a wall. It disappears the moment they click the link. --}}
    @if (config('escalate.beta.require_verification')
        && auth()->check()
        && ! auth()->user()->hasVerifiedEmail()
        && ! request()->routeIs('verification.*'))
        <div class="notice notice-warn" role="status">
            @include('partials.icon', ['name' => 'alert', 'size' => 18])
            <div>
                <strong>Confirm your email to start writing.</strong>
                Everything else works — this only opens the readings and the voice.
                <a href="{{ route('verification.notice') }}">Send the link again &rarr;</a>
            </div>
        </div>
    @endif

    {{-- The paywall, such as it is. Quota::message() decides whether an
         upgrade is honestly on offer — never shown to someone already on the
         largest plan, who really does just have to wait — and this turns that
         sentence into somewhere to go. --}}
    @if (config('escalate.billing.enabled')
        && session('status')
        && str_contains(session('status'), 'free plan’s')
        && ! request()->routeIs('billing.*'))
        <div class="notice" role="status">
            @include('partials.icon', ['name' => 'info', 'size' => 18])
            <div>
                {{ session('status') }}
                <a href="{{ route('billing.index') }}">See the plans &rarr;</a>
            </div>
        </div>
    @endif

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
