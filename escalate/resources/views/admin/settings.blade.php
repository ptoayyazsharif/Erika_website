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
