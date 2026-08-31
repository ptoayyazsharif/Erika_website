@extends('layouts.app', ['title' => 'Your feedback'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Seven days in</p>
    <h1>{{ $existing ? 'Thank you — you can change this.' : 'Tell us the truth.' }}</h1>
    <p class="lede">
        Four questions. The first is the one that matters; the rest are optional
        and worth more than the first if you answer them honestly.
    </p>
</div>

<form method="POST" action="{{ route('feedback.store') }}" class="card" data-once>
    @csrf

    <div class="field">
        <p class="label" style="margin-bottom:var(--s-3)">
            1. How would you feel if you could no longer use Escalate?
        </p>

        @foreach (\App\Models\FeedbackResponse::FEELINGS as $value => $label)
            @php $on = old('disappointment', $existing?->disappointment) === $value; @endphp
            <label class="option {{ $on ? 'is-on' : '' }}" style="margin-bottom:var(--s-3)">
                <input type="radio" name="disappointment" value="{{ $value }}" @checked($on)>
                <span class="tick" aria-hidden="true"></span>
                <span class="option-body"><span class="option-label">{{ $label }}</span></span>
            </label>
        @endforeach

        @error('disappointment')<span class="error">{{ $message }}</span>@enderror
    </div>

    @foreach ([
        ['who_for', '2. What type of person do you think would benefit most from Escalate?', 'In your own words — not a market, a person.'],
        ['benefit', '3. What is the main benefit you get from it?', 'The one you would mention first if a friend asked.'],
        ['improve', '4. How can we improve it for you?', 'Be blunt. This is the answer we can act on.'],
    ] as [$field, $question, $hint])
        <div class="field">
            <label for="{{ $field }}">{{ $question }}</label>
            <span class="hint">{{ $hint }}</span>
            <textarea class="textarea" id="{{ $field }}" name="{{ $field }}" maxlength="2000"
                      style="min-height:96px">{{ old($field, $existing?->$field) }}</textarea>
            @error($field)<span class="error">{{ $message }}</span>@enderror
        </div>
    @endforeach

    <button class="btn btn-full" type="submit" data-busy="Sending…">
        {{ $existing ? 'Update my answers' : 'Send my feedback' }}
    </button>
</form>

<p class="small faint center" style="margin-top:var(--s-5)">
    Only Erika reads these. They are stored encrypted, like everything else you
    write here.
</p>

<p class="small muted center" style="margin-top:var(--s-4)">
    <a class="link-back" href="{{ route('today') }}">Back to Today</a>
</p>
@endsection
