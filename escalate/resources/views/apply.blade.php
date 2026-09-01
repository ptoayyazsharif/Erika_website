@extends('layouts.auth', ['title' => 'Request access'])

@section('heading', 'Request private access.')
@section('sub', 'Founding testers. Shaping what this becomes.')

@section('form')
<p class="small muted" style="margin-bottom:var(--s-5)">
    {{ config('escalate.copy.questions_intro') }}
</p>

<form method="POST" action="{{ route('apply.store') }}" class="card" data-once>
    @csrf

    {{-- Not display:none. A field hidden that way is the first thing a bot
         looks for; one placed off-screen and taken out of the tab order is
         invisible to a person and ordinary to a script. --}}
    <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
        <label for="website">Leave this empty</label>
        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
    </div>

    <div class="field">
        <label for="name">Your name</label>
        <input class="input" id="name" name="name" type="text" maxlength="80" required
               value="{{ old('name') }}" autocomplete="name"
               @error('name') aria-invalid="true" @enderror>
        @error('name')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input class="input" id="email" name="email" type="email" maxlength="255" required
               value="{{ old('email') }}" autocomplete="email"
               @error('email') aria-invalid="true" @enderror>
        <span class="hint">This is where the invite goes, if you are selected.</span>
        @error('email')<span class="error">{{ $message }}</span>@enderror
    </div>

    <div class="rule">Five questions</div>

    {{-- The five questions come from config so Erika can reword them from the
         admin panel without a deploy. Keyed by the column the answer lands in,
         so the question and the label above that answer cannot drift apart. --}}
    @foreach (\App\Support\Copy::questions() as $i => [$field, $question, $hint])
        <div class="field">
            <label for="{{ $field }}">{{ $i + 1 }}. {{ $question }}</label>
            @if ($hint)<span class="hint">{{ $hint }}</span>@endif
            <textarea class="textarea" id="{{ $field }}" name="{{ $field }}" maxlength="2000" required
                      style="min-height:96px"
                      @error($field) aria-invalid="true" @enderror>{{ old($field) }}</textarea>
            @error($field)<span class="error">{{ $message }}</span>@enderror
        </div>
    @endforeach

    <label class="option {{ old('agree') ? 'is-on' : '' }}" style="margin:var(--s-5) 0">
        <input type="checkbox" name="agree" value="1" @checked(old('agree'))>
        <span class="tick" aria-hidden="true"></span>
        <span class="option-body">
            <span class="option-label">I’ve read what happens to what I write</span>
            <small><a href="{{ route('privacy') }}">Read it here</a> — it takes a minute.</small>
        </span>
    </label>
    @error('agree')<span class="error">{{ $message }}</span>@enderror

    {{-- Iridescent here and nowhere else on this page: the brief reserves the
         gradient for transformation moments, and this button is the only thing
         on the public site that changes anybody's state. --}}
    <button class="btn btn-iridescent btn-full" type="submit" data-busy="Sending…">Request private access</button>
</form>

<p class="small faint center" style="margin-top:var(--s-5)">
    Spots are limited. We’ll be in touch by email either way.
</p>

<p class="small muted center" style="margin-top:var(--s-4)">
    Already have a code? <a href="{{ route('register') }}">Use your invite</a>
</p>
@endsection
