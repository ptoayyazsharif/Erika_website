@extends('layouts.app', ['title' => 'Settings'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Settings</h1>
    <p class="lede">
        These override what the server was deployed with, and take effect
        immediately — no redeploy. Clear a field to fall back to the deployed
        value.
    </p>
</div>

@include('admin._nav')

<div class="notice" role="status" data-enter>
    @include('partials.icon', ['name' => 'lock', 'size' => 18])
    <div>
        API keys can be replaced here but never read back. A saved key shows only
        its last four characters — an admin session is the one most worth
        stealing, and a page that prints live keys would turn one stolen session
        into two stolen vendor accounts.
    </div>
</div>

{{-- Stripe connection check. Read-only: it retrieves from Stripe and writes
     nothing on either side, so it is safe to press at any time. --}}
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

<form method="POST" action="{{ route('admin.settings.update') }}" data-once>
    @csrf
    @method('PUT')

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
