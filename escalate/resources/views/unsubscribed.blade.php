@extends('layouts.auth', ['title' => 'Email settings'])

@section('heading', ($resubscribed ?? false) ? 'You are back on the list.' : 'That is done.')
@section('sub', ($resubscribed ?? false)
    ? 'You will hear from us when there is something worth saying.'
    : 'No more announcement emails.')

@section('form')
<div class="card" data-enter>
    @if ($resubscribed ?? false)
        <p style="margin:0 0 var(--s-4)">
            Announcement emails are on again for <strong>{{ $person->email }}</strong>.
        </p>
    @else
        <p style="margin:0 0 var(--s-4)">
            We will not email <strong>{{ $person->email }}</strong> about news or
            updates again.
        </p>

        <p class="small muted" style="margin:0 0 var(--s-5)">
            Anything you asked for still reaches you — an invite, a password
            reset, confirming your address. Those are replies to something you
            did, not announcements.
        </p>

        {{-- A link in an email can be followed by a scanner before the person
             ever sees it, so the click is not treated as final. --}}
        <a class="btn btn-quiet btn-full"
           href="{{ \Illuminate\Support\Facades\URL::signedRoute('announcements.resubscribe', ['user' => $person->id]) }}">
            Undo — I did not mean to
        </a>
    @endif
</div>

<p class="small muted center" style="margin-top:var(--s-5)">
    <a href="{{ route('today') }}">Back to Escalate</a>
</p>
@endsection
