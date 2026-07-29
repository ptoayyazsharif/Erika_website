@extends('layouts.app', ['title' => isset($desire) ? 'Edit desire' : 'Name a desire'])

@php
    $d = $desire ?? null;
    $cats = \App\Http\Controllers\DesireController::CATEGORIES;
    $times = \App\Http\Controllers\DesireController::TIMEFRAMES;
    $feels = \App\Http\Controllers\DesireController::FEELINGS;
    $chosenFeelings = old('desired_feelings', $d?->desired_feelings ?? []);
    $chosenPeople = old('people_involved', $d?->people_involved ?? []);
@endphp

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">{{ $d ? 'Edit' : 'New' }}</p>
    <h1>{{ $d ? 'Change what this is.' : 'What do you want?' }}</h1>
    <p class="lede">Write it as though it were already the case. Specific beats poetic.</p>
</div>

<form method="POST" action="{{ $d ? route('desires.update', $d) : route('desires.store') }}" data-once>
    @csrf
    @if ($d) @method('PUT') @endif

    <div class="card" data-enter>
        <div class="field">
            <label for="title">In one line</label>
            <input class="input" id="title" name="title" type="text" required autofocus
                   maxlength="120" value="{{ old('title', $d?->title) }}"
                   placeholder="A house on the water, paid for by work I&rsquo;m proud of">
        </div>

        <div class="field">
            <label for="description">In your own words</label>
            <span class="hint">What does an ordinary Tuesday look like once this is true? Names, streets, numbers, smells.</span>
            <textarea class="textarea" id="description" name="description" maxlength="2000"
                      data-autogrow data-counter="desc-count">{{ old('description', $d?->description) }}</textarea>
            <div class="counter" id="desc-count"></div>
        </div>

        <div class="field" style="margin-bottom:0">
            <label for="why_it_matters">Why does it matter?</label>
            <span class="hint">The real reason, not the presentable one.</span>
            <textarea class="textarea" id="why_it_matters" name="why_it_matters" maxlength="1200"
                      data-autogrow style="min-height:96px">{{ old('why_it_matters', $d?->why_it_matters) }}</textarea>
        </div>
    </div>

    <div class="card" data-enter>
        <div class="field">
            <label class="label" for="category">What part of your life?</label>
            <select class="select" id="category" name="category">
                @foreach ($cats as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $d?->category) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="timeframe">When?</label>
            <select class="select" id="timeframe" name="timeframe">
                @foreach ($times as $key => $label)
                    <option value="{{ $key }}" @selected(old('timeframe', $d?->timeframe ?? 'this_year') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <span class="label">How do you want to feel?</span>
            <span class="hint">Up to five.</span>
            <div class="chips">
                @foreach ($feels as $feeling)
                    <label class="chip {{ in_array($feeling, $chosenFeelings, true) ? 'is-on' : '' }}">
                        <input type="checkbox" name="desired_feelings[]" value="{{ $feeling }}"
                               style="position:absolute;opacity:0;pointer-events:none"
                               @checked(in_array($feeling, $chosenFeelings, true))>
                        {{ ucfirst($feeling) }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="field">
            <span class="label">Who is part of it?</span>
            <span class="hint">From My Circle. Only these names can appear in the reading.</span>
            @if ($circle->isEmpty())
                <p class="small faint" style="margin:0">
                    No one in your circle yet — <a href="{{ route('world.edit') }}">add people in My World</a>.
                </p>
            @else
                <div class="options">
                    @foreach ($circle as $person)
                        <label class="option {{ in_array($person->name, $chosenPeople, true) ? 'is-on' : '' }}">
                            <input type="checkbox" name="people_involved[]" value="{{ $person->name }}"
                                   @checked(in_array($person->name, $chosenPeople, true))>
                            <span class="tick" aria-hidden="true"></span>
                            <span class="option-body">
                                <span class="option-label">{{ $person->name }}</span>
                                @if (filled($person->relationship))<small>{{ $person->relationship }}</small>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="field" style="margin-bottom:0">
            <label for="non_negotiables">Anything non-negotiable?</label>
            <span class="hint">Something that has to be true, or the version you don’t want.</span>
            <input class="input" id="non_negotiables" name="non_negotiables" type="text" maxlength="600"
                   value="{{ old('non_negotiables', $d?->non_negotiables) }}"
                   placeholder="Not at the cost of the mornings with Maya">
        </div>
    </div>

    <div class="card" data-enter>
        <h3 style="margin-bottom:var(--s-5)">How this one should be written</h3>

        <label class="option {{ old('open_to_better', $d?->open_to_better ?? true) ? 'is-on' : '' }}" style="margin-bottom:var(--s-5)">
            <input type="checkbox" name="open_to_better" value="1"
                   @checked(old('open_to_better', $d?->open_to_better ?? true))>
            <span class="tick" aria-hidden="true"></span>
            <span class="option-body">
                <span class="option-label">Open to something better</span>
                <small>The reading may let one detail arrive differently — and better — than you pictured.</small>
            </span>
        </label>

        <div class="field">
            <label class="label" for="story_length">Length</label>
            <select class="select" id="story_length" name="story_length">
                @foreach (config('escalate.lengths') as $key => $len)
                    <option value="{{ $key }}" @selected(old('story_length', $d?->story_length ?? $profile->default_length) === $key)>{{ $len['label'] }} — {{ $len['note'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="label" for="perspective">Perspective</label>
            <select class="select" id="perspective" name="perspective">
                <option value="first" @selected(old('perspective', $d?->perspective ?? $profile->perspective) === 'first')>First person — “I am”</option>
                <option value="second" @selected(old('perspective', $d?->perspective ?? $profile->perspective) === 'second')>Second person — “you are”</option>
            </select>
        </div>

        <div class="field" style="margin-bottom:0">
            <label class="label" for="tone">Tone</label>
            <select class="select" id="tone" name="tone">
                @foreach (['grounded' => 'Grounded', 'tender' => 'Tender', 'assured' => 'Assured', 'reverent' => 'Reverent', 'playful' => 'Playful'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('tone', $d?->tone ?? $profile->tone) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row wrap" style="margin-top:var(--s-6);gap:var(--s-3)">
        <button class="btn" type="submit" data-busy="Saving…">{{ $d ? 'Save changes' : 'Name it' }}</button>
        <a class="btn btn-quiet" href="{{ $d ? route('desires.show', $d) : route('desires.index') }}">Cancel</a>
    </div>
</form>
@endsection
