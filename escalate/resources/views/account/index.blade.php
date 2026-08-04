@extends('layouts.app', ['title' => 'Your account'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Your account</p>
    <h1>It’s yours to take, or to end.</h1>
    <p class="lede">Both of these need your password again — an unlocked phone left on a table should not be able to do either.</p>
</div>

<div class="card" data-enter>
    <h3 style="margin-bottom:var(--s-2)">Take a copy</h3>
    <p class="small muted" style="margin-bottom:var(--s-5)">
        Everything you have written — My World, your circle, every desire, every
        reading, every gratitude entry and every rewind — as a single readable
        file. Audio isn’t included; download those from the reading itself.
    </p>

    <form method="POST" action="{{ route('account.export') }}">
        @csrf
        <div class="field">
            <label class="label" for="export-password">Your password</label>
            <input class="input" id="export-password" name="password" type="password"
                   autocomplete="current-password" required>
        </div>
        <button class="btn btn-ghost" type="submit">
            @include('partials.icon', ['name' => 'download', 'size' => 16])
            Download my data
        </button>
    </form>
</div>

<div class="card" data-enter style="border-color:color-mix(in srgb, var(--danger) 34%, transparent)">
    <h3 style="margin-bottom:var(--s-2)">Delete everything</h3>
    <p class="small muted" style="margin-bottom:var(--s-4)">
        This removes your account, every reading, every entry, and every audio
        file from the server. It cannot be undone and we cannot recover it for
        you afterwards. Take a copy first if you might want one.
    </p>
    <p class="small muted" style="margin-bottom:var(--s-5)">
        Readings already sent to our AI providers to be written or narrated are
        covered by their retention policies, not ours —
        <a href="{{ route('privacy') }}">what we send them, and why</a>.
    </p>

    <form method="POST" action="{{ route('account.destroy') }}"
          data-confirm="Delete your account and everything in it? This cannot be undone.">
        @csrf
        @method('DELETE')

        <div class="field">
            <label class="label" for="delete-password">Your password</label>
            <input class="input" id="delete-password" name="password" type="password"
                   autocomplete="current-password" required>
        </div>

        <div class="field">
            <label class="label" for="confirm">Type <strong>DELETE</strong> to confirm</label>
            <input class="input" id="confirm" name="confirm" type="text"
                   autocomplete="off" spellcheck="false" required placeholder="DELETE">
        </div>

        <button class="btn btn-danger" type="submit">Delete my account</button>
    </form>
</div>

<p class="small muted center" style="margin-top:var(--s-6)">
    <a class="link-back" href="{{ route('world.edit') }}">Back to My World</a>
</p>
@endsection
