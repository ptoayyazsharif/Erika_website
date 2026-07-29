@extends('layouts.app', ['title' => 'Change a word'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Your words now</p>
    <h1>Change anything.</h1>
    <p class="lede">
        Once you edit a reading it is yours, and the original is not kept. The
        narration is cleared too — it read the old words.
    </p>
</div>

<form method="POST" action="{{ route('stories.update', $story) }}" data-once>
    @csrf
    @method('PUT')

    <div class="field">
        <label class="sr-only" for="edited_body">The reading</label>
        <textarea class="textarea" id="edited_body" name="edited_body" required
                  minlength="80" maxlength="12000" data-autogrow data-counter="body-count"
                  style="min-height:60vh;font-size:var(--t-md);line-height:1.85">{{ old('edited_body', $story->text()) }}</textarea>
        <div class="counter" id="body-count"></div>
    </div>

    <div class="row wrap" style="gap:var(--s-3)">
        <button class="btn" type="submit" data-busy="Saving…">Save my version</button>
        <a class="btn btn-quiet" href="{{ route('stories.show', $story) }}">Cancel</a>
    </div>
</form>
@endsection
