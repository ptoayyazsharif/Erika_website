@extends('layouts.app', ['title' => 'Begin a Rewind'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">{{ $desire->statusLabel() }}</p>
    <h1>{{ $desire->title }}</h1>
    <p class="lede">
        Six questions, all optional. Answer the ones that have an answer and leave
        the rest — a Rewind is written from what you say, so a blank is better
        than something made up.
    </p>
</div>

<form method="POST" action="{{ route('rewinds.store', $desire) }}" data-once>
    @csrf

    @php
        // label, name, placeholder, rows, maxlength. `redirections` is the one
        // worth asking even when it did not happen: it is the question that
        // turns a disappointment into something a person can read back.
        //
        // The limits mirror RewindController::store exactly. Every box used to
        // say 4000 while the server capped `people` and `gratitude` at 2000, so
        // typing a long answer into either one — the two questions most likely
        // to run long, since they are the list of everyone involved and
        // everything to be thankful for — hit a validation error after pressing
        // Save, with no warning while writing it.
        $questions = [
            ['What happened?', 'what_happened', 'Start wherever it starts.', 6, 4000],
            ['What were the turning points?', 'turning_points', 'The moments it changed direction.', 4, 4000],
            ['Did it arrive as something else?', 'redirections', 'If what came was not what you asked for.', 4, 4000],
            ['Who was part of it?', 'people', 'Names, as you would say them.', 3, 2000],
            ['What did you learn?', 'lessons', 'Only if you did. It is fine if not.', 4, 4000],
            ['What are you grateful for?', 'gratitude', 'Anything, however small.', 3, 2000],
        ];
    @endphp

    <div class="card" data-enter>
        @foreach ($questions as [$label, $name, $placeholder, $rows, $max])
            <div class="field">
                <label class="label" for="{{ $name }}">{{ $label }}</label>
                <textarea class="textarea" id="{{ $name }}" name="{{ $name }}"
                          rows="{{ $rows }}" maxlength="{{ $max }}" data-autogrow
                          placeholder="{{ $placeholder }}">{{ old($name, $rewind?->{$name}) }}</textarea>
            </div>
        @endforeach

        <button class="btn btn-full" type="submit" data-busy="Saving…">
            @include('partials.icon', ['name' => 'check', 'size' => 16])
            Save the Rewind
        </button>

        <p class="small faint center" style="margin-top:var(--s-3)">
            Saved as a draft. You write it up afterwards, whenever you are ready.
        </p>
    </div>
</form>

<a class="link-back" href="{{ route('desires.show', $desire) }}">Back to {{ Str::limit($desire->title, 40) }}</a>
@endsection
