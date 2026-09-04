@extends('layouts.app', ['title' => $meta['label']])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow"><a href="{{ route('admin.settings') }}">Settings</a></p>
    <h1>{{ $meta['label'] }}</h1>
    <p class="lede">{{ $meta['blurb'] }}</p>
</div>

@include('admin._nav')

<div class="row wrap" style="gap:var(--s-2);margin-bottom:var(--s-5)">
    @foreach ($sections as $key => $item)
        <a class="chip {{ $key === $section ? 'is-on' : '' }}"
           href="{{ route('admin.settings.section', $key) }}">{{ $item['label'] }}</a>
    @endforeach
</div>

@if ($section === 'money' || $section === 'ai')
    <div class="notice" role="status" data-enter>
        @include('partials.icon', ['name' => 'lock', 'size' => 18])
        <div>
            API keys can be replaced here but never read back. A saved key shows only
            its last four characters — an admin session is the one most worth
            stealing, and a page that prints live keys would turn one stolen session
            into two stolen vendor accounts.
        </div>
    </div>
@endif

{{-- Stripe connection check. Read-only: it retrieves from Stripe and writes
     nothing on either side, so it is safe to press at any time. --}}
@if ($section === 'money')
<div class="card" data-enter>
    <div class="row-between wrap" style="gap:var(--s-3)">
        <div>
            <h3 style="margin:0 0 var(--s-2)">Check the Stripe setup</h3>
            <p class="small muted" style="margin:0">
                Verifies the keys for the mode you are in, that every plan's price id
                actually resolves, and that a webhook secret is present. It only reads —
                nothing is created in Stripe and nothing here is changed.
            </p>
        </div>
        <form method="POST" action="{{ route('admin.settings.stripe') }}" data-once>
            @csrf
            <button class="btn btn-ghost" type="submit" data-busy="Checking…">Run the check</button>
        </form>
    </div>

    @if ($result = session('stripe_check'))
        <div class="rule">{{ $result['mode'] }} mode — {{ $result['ok'] ? 'all clear' : 'needs attention' }}</div>

        @foreach ($result['checks'] as $c)
            @php $colour = ['pass'=>'var(--text-muted)','warn'=>'var(--brass)','fail'=>'var(--danger)'][$c['state']]; @endphp
            <div style="margin-bottom:var(--s-4)">
                <p class="small" style="margin:0 0 2px;color:{{ $colour }}">
                    <strong>{{ $c['state'] === 'pass' ? '✓' : ($c['state'] === 'warn' ? '!' : '✕') }} {{ $c['name'] }}</strong>
                </p>
                <p class="small muted" style="margin:0">{{ $c['detail'] }}</p>
            </div>
        @endforeach
    @endif
</div>
@endif

@if ($section === 'mail')
<div class="card" data-enter>
    <div class="row-between wrap" style="gap:var(--s-3)">
        <div>
            <h3 style="margin:0 0 var(--s-2)">Send a test email</h3>
            <p class="small muted" style="margin:0">
                Sends one real message to <strong>{{ auth()->user()->email }}</strong> using the
                mail settings below. The only honest test of mail is mail that arrives —
                a configuration that looks right and quietly fails is the normal way this breaks.
            </p>
        </div>
        <form method="POST" action="{{ route('admin.settings.mail') }}" data-once>
            @csrf
            <button class="btn btn-ghost" type="submit" data-busy="Sending…">Send it</button>
        </form>
    </div>
</div>
@endif

{{-- Outside the settings form on purpose: a <form> cannot nest inside another
     one, and this is its own submission. --}}
@if ($section === 'reminders')
@php
    $pushReady = \App\Support\Push::configured();
    $pushDevices = $pushReady ? \App\Models\PushSubscription::query()->reachable()->count() : 0;
    $pushPeople = $pushReady
        ? \App\Models\PushSubscription::query()->reachable()->distinct()->count('user_id')
        : 0;
@endphp

{{-- There is deliberately no button here once the keys exist.

     Generating a second pair invalidates every device at once — each one
     subscribed with the current public key and the push service rejects
     anything signed by another. That is a real need roughly never, and a
     destructive control on a settings page is a control that eventually gets
     pressed. Rotating a leaked key is still possible: paste a pair into the two
     fields below, or use the reset links under the form. Two deliberate steps
     that each say what they do, instead of one button.

     SettingsController::pushKeys() refuses when keys already exist, so the URL
     is closed too. Hiding a button is not the same as removing the hazard. --}}
<div class="card" data-enter>
    <div class="row-between wrap" style="gap:var(--s-3);align-items:flex-start">
        <div>
            <h3 style="margin:0 0 var(--s-2)">
                {{ $pushReady ? 'Notifications are set up' : 'Notifications are not set up yet' }}
            </h3>

            @if ($pushReady)
                <p class="small muted" style="margin:0">
                    Notifications can be sent. @if ($pushDevices === 0)
                        Nobody has switched them on yet — testers do that in My World, on a
                        phone with the app installed.
                    @else
                        {{ $pushDevices }} {{ Str::plural('device', $pushDevices) }}, belonging
                        to {{ $pushPeople }} {{ Str::plural('person', $pushPeople) }},
                        {{ $pushDevices === 1 ? 'has' : 'have' }} them switched on.
                    @endif
                </p>
                <p class="small faint" style="margin:var(--s-2) 0 0">
                    This is done. There is nothing to press again, and nothing here that a
                    deploy can undo — the keys live in the database on the persistent volume.
                </p>
            @else
                <p class="small muted" style="margin:0">
                    Notifications need a keypair to sign with. Press this once and it is done —
                    the pair is generated here and stored encrypted, so there is nothing to
                    copy anywhere and nothing to deploy.
                </p>
            @endif
        </div>

        @unless ($pushReady)
            <form method="POST" action="{{ route('admin.settings.push-keys') }}" data-once>
                @csrf
                <button class="btn" type="submit" data-busy="Generating…">Generate the keys</button>
            </form>
        @endunless
    </div>
</div>
@endif

{{-- Previews. Outside the settings form on purpose: a <form> cannot nest
     inside another one, and each preview is its own submission. --}}
@if ($section === 'emails')
<div class="card" data-enter>
    <h3 style="margin:0 0 var(--s-2)">See one in a real inbox</h3>
    <p class="small muted" style="margin:0 0 var(--s-4)">
        Sends the wording as it stands right now to
        <strong>{{ auth()->user()->email }}</strong>. Save first — a preview shows
        what is stored, not what is typed on screen. Codes and buttons are
        stand-ins; the real email fills them in.
    </p>

    <div class="row wrap" style="gap:var(--s-2)">
        @foreach (App\Support\EmailTemplates::TEMPLATES as $key => $meta)
            <form method="POST" action="{{ route('admin.settings.email-test', $key) }}" data-once>
                @csrf
                <button class="btn btn-quiet btn-sm" type="submit" data-busy="Sending…">
                    {{ $meta['label'] }}
                </button>
            </form>
        @endforeach
    </div>
</div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" data-once>
    @csrf
    @method('PUT')

    {{-- Which page was saved. The controller scopes its write to this section's
         fields; without it, saving here would read every checkbox on every
         OTHER page as unticked and switch them all off. --}}
    <input type="hidden" name="section" value="{{ $section }}">

    @foreach ($groups as $group => $fields)
        <div class="card" data-enter>
            <h3 style="margin-bottom:var(--s-5)">{{ $group }}</h3>

            @foreach ($fields as $key => $meta)
                @php $field = str_replace('.', '__', $key); $shown = $display[$key]; @endphp

                @if ($meta['type'] === 'bool' || $meta['type'] === 'mode')
                    @php $on = $meta['type'] === 'mode' ? (config($key) === 'test') : (bool) config($key); @endphp
                    <label class="option {{ $on ? 'is-on' : '' }}" style="margin-bottom:var(--s-4)">
                        <input type="checkbox" name="settings[{{ $field }}]" value="1" @checked($on)>
                        <span class="tick" aria-hidden="true"></span>
                        <span class="option-body">
                            <span class="option-label">{{ $meta['label'] }}</span>
                            @isset($meta['help'])<small>{{ $meta['help'] }}</small>@endisset
                        </span>
                    </label>
                @elseif ($meta['type'] === 'text')
                    <div class="field">
                        <label for="{{ $field }}">{{ $meta['label'] }}</label>
                        @isset($meta['help'])<span class="hint">{{ $meta['help'] }}</span>@endisset

                        <textarea class="textarea" id="{{ $field }}" name="settings[{{ $field }}]"
                                  style="min-height:96px" maxlength="2000">{{ $shown['value'] }}</textarea>

                        <div class="row small faint" style="gap:var(--s-3);margin-top:4px">
                            @if (\App\Support\Settings::isOverridden($key))
                                <span>Edited here. Clear the box to go back to the original wording.</span>
                            @endif
                        </div>
                    </div>
                @elseif ($meta['type'] === 'choice')
                    <div class="field">
                        <label for="{{ $field }}">{{ $meta['label'] }}</label>
                        @isset($meta['help'])<span class="hint">{{ $meta['help'] }}</span>@endisset

                        <select class="select" id="{{ $field }}" name="settings[{{ $field }}]">
                            @foreach ($meta['options'] as $value => $optionLabel)
                                <option value="{{ $value }}" @selected(config($key) === $value)>{{ $optionLabel }}</option>
                            @endforeach
                        </select>

                        @if (\App\Support\Settings::isOverridden($key))
                            <div class="row small faint" style="gap:var(--s-3);margin-top:4px">
                                <span>Overridden here.</span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="field">
                        <label for="{{ $field }}">{{ $meta['label'] }}</label>
                        @isset($meta['help'])<span class="hint">{{ $meta['help'] }}</span>@endisset

                        <input class="input" id="{{ $field }}" name="settings[{{ $field }}]"
                               type="{{ $meta['type'] === 'int' ? 'number' : 'text' }}"
                               @if ($meta['type'] === 'int') min="0" step="1" @endif
                               autocomplete="off" spellcheck="false"
                               value="{{ $meta['type'] === 'secret' ? '' : $shown['value'] }}"
                               @if ($meta['type'] === 'secret')
                                   placeholder="{{ $shown['hint'] ?? 'Not set' }}"
                               @endif>

                        <div class="row small faint" style="gap:var(--s-3);margin-top:4px">
                            @if ($meta['type'] === 'secret')
                                <span>{{ $shown['set'] ? 'Set — leave blank to keep it.' : 'Not set.' }}</span>
                            @elseif ($meta['keep_when_blank'] ?? false)
                                <span>{{ $shown['set'] ? 'Leave blank to keep it.' : 'Not set.' }}</span>
                            @endif
                            @if (\App\Support\Settings::isOverridden($key))
                                <span>Overridden here.</span>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach

    <button class="btn" type="submit" data-busy="Saving…">Save settings</button>
</form>

<div class="rule">Falling back</div>

<p class="small muted">
    Any setting changed here can be returned to the value in the environment.
    That is the safe move when something has gone wrong and you are not sure
    what: it puts the app back to what was deployed.
</p>

<div class="chips">
    @foreach ($groups as $group => $fields)
        @foreach ($fields as $key => $meta)
            @if (\App\Support\Settings::isOverridden($key))
                <form method="POST" action="{{ route('admin.settings.reset') }}"
                      data-confirm="Reset “{{ $meta['label'] }}” to the deployed value?">
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    <button class="chip" type="submit">Reset {{ $meta['label'] }}</button>
                </form>
            @endif
        @endforeach
    @endforeach
</div>
@endsection
