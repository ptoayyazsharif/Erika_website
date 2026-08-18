@extends('layouts.app', ['title' => 'My World'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">My World</p>
    <h1>{{ $profile->onboarded ? 'The things worth knowing about you.' : 'Before anything is written.' }}</h1>
    <p class="lede">
        Every reading is built from what is on this page. The more specific you
        are, the less it sounds like it could be about anyone.
    </p>
</div>

<form method="POST" action="{{ route('world.update') }}" data-once>
    @csrf
    @method('PUT')

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-5)">You</h3>

        <div class="field">
            <label for="preferred_name">What should the readings call you?</label>
            <span class="hint">It appears in the text exactly as you write it.</span>
            <input class="input" id="preferred_name" name="preferred_name" type="text"
                   maxlength="60" value="{{ old('preferred_name', $profile->preferred_name) }}"
                   placeholder="{{ auth()->user()->name }}">
        </div>

        <div class="field">
            <label for="city">Where does your life happen?</label>
            <input class="input" id="city" name="city" type="text" maxlength="80"
                   value="{{ old('city', $profile->city) }}" placeholder="Atlanta">
        </div>

        <div class="field">
            <label for="life_context">Where are you right now?</label>
            <span class="hint">Not where you want to be — where you actually are. This is what the contrast in a reading is measured against.</span>
            <textarea class="textarea" id="life_context" name="life_context" maxlength="1200"
                      data-autogrow data-counter="ctx-count"
                      placeholder="Two years into building something, still checking the balance more often than I&rsquo;d like to admit.">{{ old('life_context', $profile->life_context) }}</textarea>
            <div class="counter" id="ctx-count"></div>
        </div>

        <div class="field" style="margin-bottom:0">
            <label for="anchor">A place or object that grounds you</label>
            <span class="hint">Somewhere the readings can return to. A kitchen at six in the morning, a particular chair, the walk to the corner.</span>
            <input class="input" id="anchor" name="anchor" type="text" maxlength="200"
                   value="{{ old('anchor', $profile->anchor) }}"
                   placeholder="the back step, before anyone else is up">
        </div>
    </div>

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-2)">What matters most</h3>
        <p class="small muted" style="margin-bottom:var(--s-5)">
            Write up to six, in your own words. These shape what a reading notices.
        </p>

        @php $values = old('values', $profile->values ?? []); @endphp
        {{-- Not .options. That class means "pick one of these", and six bordered
             boxes each showing a single word read as exactly that — you tap one,
             nothing selects, and you report that the buttons do not work. These
             are typed, so they are laid out as fields and the placeholders say
             "e.g." to settle which they are. --}}
        <div class="field-grid">
            @for ($i = 0; $i < 6; $i++)
                <input class="input" name="values[]" type="text" maxlength="40"
                       value="{{ $values[$i] ?? '' }}"
                       aria-label="Value {{ $i + 1 }}"
                       placeholder="e.g. {{ ['Freedom','Family','Steadiness','Craft','Generosity','Health'][$i] }}">
            @endfor
        </div>
    </div>

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-2)">My Circle</h3>
        <p class="small muted" style="margin-bottom:var(--s-5)">
            The people who belong in your stories. Names are used exactly as written, and only where they fit.
        </p>

        @php
            // Rows come from a failed submission first, then from the database.
            // Both are normalised to the same shape so the markup below does not
            // have to care which one it got.
            $circleRows = old('circle', $circle->map(fn ($p) => [
                'name'         => $p->name,
                'relationship' => $p->relationship,
                'notes'        => $p->details(),
            ])->all());

            // Always leave one empty row to type into, so adding the first
            // person needs no button press.
            if (! $circleRows) {
                $circleRows = [['name' => '', 'relationship' => '', 'notes' => []]];
            }
        @endphp

        {{-- data-circle marks the list app.js appends to. The template below is
             inert markup inside <template>, cloned on demand — the CSP forbids
             inline script, so the browser cannot build these rows from a string. --}}
        <div data-circle>
            @foreach ($circleRows as $i => $row)
                @php $notes = array_values(array_filter((array) ($row['notes'] ?? []), 'filled')) ?: ['']; @endphp
                <div class="circle-person" data-person>
                    <input class="input" name="circle[{{ $i }}][name]" type="text" maxlength="60"
                           value="{{ $row['name'] ?? '' }}" aria-label="Person {{ $i + 1 }} name"
                           placeholder="Name">
                    <input class="input" name="circle[{{ $i }}][relationship]" type="text" maxlength="60"
                           value="{{ $row['relationship'] ?? '' }}" aria-label="Person {{ $i + 1 }} relationship"
                           placeholder="Who they are to you now">

                    <div data-notes>
                        @foreach ($notes as $n => $note)
                            <input class="input" name="circle[{{ $i }}][notes][]" type="text" maxlength="200"
                                   value="{{ $note }}" aria-label="Detail {{ $n + 1 }} about person {{ $i + 1 }}"
                                   placeholder="Something true about them">
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-quiet btn-sm" data-add-note>
                        @include('partials.icon', ['name' => 'plus', 'size' => 14]) Add a detail
                    </button>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-ghost btn-sm" data-add-person style="margin-bottom:var(--s-3)">
            @include('partials.icon', ['name' => 'plus', 'size' => 16]) Add someone
        </button>

        <template data-person-template>
            <div class="circle-person" data-person>
                <input class="input" type="text" maxlength="60" data-field="name" placeholder="Name" aria-label="Name">
                <input class="input" type="text" maxlength="60" data-field="relationship"
                       placeholder="Who they are to you now" aria-label="Who they are to you">
                <div data-notes>
                    <input class="input" type="text" maxlength="200" data-field="note"
                           placeholder="Something true about them" aria-label="Detail">
                </div>
                <button type="button" class="btn btn-quiet btn-sm" data-add-note>
                    @include('partials.icon', ['name' => 'plus', 'size' => 14]) Add a detail
                </button>
            </div>
        </template>

        <p class="small faint" style="margin:0">Clear a name to remove that person. Relationships change — update "who they are to you now" whenever it does.</p>
    </div>

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-2)">How it should look</h3>
        <p class="small muted" style="margin-bottom:var(--s-5)">
            Applies the moment you pick one. Saved to your account, so it follows
            you to any device you sign in on.
        </p>

        <div class="options" data-theme-picker>
            @foreach (config('escalate.themes') as $key => $theme)
                <label class="option {{ old('theme', $profile->theme) === $key ? 'is-on' : '' }}">
                    <input type="radio" name="theme" value="{{ $key }}"
                           data-theme-choice="{{ $key }}"
                           data-theme-scheme="{{ $theme['scheme'] }}"
                           data-theme-chrome="{{ $theme['chrome'] }}"
                           data-theme-counterpart="{{ $theme['counterpart'] }}"
                           @checked(old('theme', $profile->theme) === $key)>
                    <span class="tick" aria-hidden="true"></span>
                    <span class="option-body">
                        <span class="option-label">{{ $theme['label'] }}</span>
                        <small>{{ $theme['note'] }}</small>
                    </span>
                    <span class="swatch" aria-hidden="true">
                        @foreach ($theme['swatch'] as $colour)
                            <i style="background:{{ $colour }}"></i>
                        @endforeach
                    </span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-5)">How it should sound</h3>

        <div class="field">
            <span class="label">The voice that reads to you</span>
            <div class="options">
                @foreach (config('escalate.voices') as $key => $voice)
                    <label class="option {{ old('voice', $profile->voice) === $key ? 'is-on' : '' }}">
                        <input type="radio" name="voice" value="{{ $key }}" @checked(old('voice', $profile->voice) === $key)>
                        <span class="tick" aria-hidden="true"></span>
                        <span class="option-body">
                            <span class="option-label">{{ $voice['label'] }}</span>
                            <small>{{ $voice['note'] }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="field">
            <span class="label">Faith language</span>
            <span class="hint">This decides the vocabulary a reading is allowed to use. Secular is the default.</span>
            <div class="options">
                @foreach (config('escalate.faith_languages') as $key => $label)
                    <label class="option {{ old('faith_language', $profile->faith_language ?? 'none') === $key ? 'is-on' : '' }}">
                        <input type="radio" name="faith_language" value="{{ $key }}" @checked(old('faith_language', $profile->faith_language ?? 'none') === $key)>
                        <span class="tick" aria-hidden="true"></span>
                        <span class="option-body"><span class="option-label">{{ $label }}</span></span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="field">
            <label class="label" for="story_style">Storytelling style</label>
            <select class="select" id="story_style" name="story_style">
                @foreach (['cinematic' => 'Cinematic — a scene, in real time', 'letter' => 'A letter — telling one trusted person', 'meditative' => 'Meditative — an inventory of what is true', 'documentary' => 'Documentary — what a camera would see'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('story_style', $profile->story_style) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="tone">Tone</label>
            <select class="select" id="tone" name="tone">
                @foreach (['grounded' => 'Grounded — plain and unhurried', 'tender' => 'Tender — warm and close', 'assured' => 'Assured — quietly certain', 'reverent' => 'Reverent — slow, a little formal', 'playful' => 'Playful — dry humour in the details'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('tone', $profile->tone) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="perspective">Perspective</label>
            <select class="select" id="perspective" name="perspective">
                <option value="first" @selected(old('perspective', $profile->perspective) === 'first')>First person — “I am”</option>
                <option value="second" @selected(old('perspective', $profile->perspective) === 'second')>Second person — “you are”</option>
            </select>
        </div>

        <div class="field" style="margin-bottom:0">
            <label class="label" for="default_length">Usual length</label>
            <select class="select" id="default_length" name="default_length">
                @foreach (config('escalate.lengths') as $key => $len)
                    <option value="{{ $key }}" @selected(old('default_length', $profile->default_length) === $key)>{{ $len['label'] }} — {{ $len['note'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row wrap" style="margin-top:var(--s-6);gap:var(--s-3)">
        <button class="btn" type="submit" data-busy="Saving…">Save my world</button>
        @unless ($profile->onboarded)
            <button class="btn btn-ghost" type="submit" name="then_desire" value="1">Save and name a desire</button>
        @endunless
    </div>
</form>

<div class="row wrap" style="margin-top:var(--s-7);gap:var(--s-4)">
    <form method="POST" action="{{ route('logout') }}" data-logout>
        @csrf
        <button type="submit" class="btn btn-quiet">Sign out</button>
    </form>
    <a class="btn btn-quiet" href="{{ route('account.index') }}">Your data</a>
    <a class="btn btn-quiet" href="{{ route('privacy') }}">Privacy</a>
</div>
@endsection
