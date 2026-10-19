@extends('layouts.auth', ['title' => 'What happens to what you write'])

@section('heading', 'What happens to what you write')
@section('sub', 'The short version, before the long one: your journal is encrypted on our server, and the words you write are sent to two AI companies so they can be turned into a reading and read aloud.')

@section('form')
<div class="card prose" style="font-size:var(--t-base);line-height:1.75">

    <p class="eyebrow">Where your words go</p>

    <p>
        Escalate cannot write or narrate anything by itself. To make a reading it
        sends what you have written to two companies, both in the United States:
    </p>

    <ul style="padding-left:1.1em;margin:0 0 1.15em">
        <li style="margin-bottom:.5em">
            <strong>Anthropic</strong> writes the reading. It receives the name you
            asked to be called, your city, what you wrote in “where you are right
            now”, what matters to you, your grounding place, <strong>the names and
            details of the people in your circle</strong>, and the desire the
            reading is about.
        </li>
        <li>
            <strong>ElevenLabs</strong> reads it aloud. It receives the finished
            reading, which by design contains the names, places and numbers you
            gave us.
        </li>
    </ul>

    <p>
        If you have set a faith language, an instruction derived from it goes with
        every request — including when you have chosen “secular”, which says so.
    </p>

    <p>
        Those companies keep what they receive under their own retention policies,
        not ours. If you delete your account, we cannot reach back and delete what
        they already hold.
    </p>

    <p class="eyebrow" style="margin-top:2em">About other people</p>

    <p>
        Your circle contains people who never agreed to any of this. Please only
        write about them what you would be comfortable saying in front of them,
        and leave out anything about someone else’s health, beliefs or private
        circumstances that is not yours to share.
    </p>

    <p class="eyebrow" style="margin-top:2em">What “encrypted” actually means here</p>

    <p>
        Everything you write is encrypted before it is stored, so a stolen
        database or a stray backup is unreadable. That is real, and it is more
        than most apps of this kind do.
    </p>

    <p>
        It is <strong>not</strong> end-to-end encryption. The key lives on the same
        server as the data, because the app has to read your words to send them
        for writing and narration. In plain terms:
        <strong>we could read your journal if we chose to.</strong>
        We do not, there is no screen in the app that shows it to us, and an
        administrator supporting you sees only counts and dates. But you should
        know the difference between “we do not” and “we cannot”, and this is the
        former.
    </p>

    <p class="eyebrow" style="margin-top:2em">What stays on your device</p>

    <p>
        Nothing. No story, no entry and no audio is cached on your phone or
        laptop — that is why the app needs a connection even to reread something
        you have already seen. Signing out clears what little the browser held.
    </p>

    <p class="eyebrow" style="margin-top:2em">Cookies</p>

    <p>
        Only the ones that make signing in work. There is no analytics, no
        advertising, no tracking pixel, and nothing is loaded from another
        company’s server — no fonts, no scripts, no images.
    </p>

    <p class="eyebrow" style="margin-top:2em">Leaving</p>

    <p>
        You can download everything we hold, or delete your account and every file
        with it, from <a href="{{ route('account.index') }}">your account page</a>.
        Deletion is immediate and permanent.
    </p>

    <p class="eyebrow" style="margin-top:2em">Age</p>

    <p>
        Escalate is for adults. You must be 16 or over to use it.
    </p>

    <p class="eyebrow" style="margin-top:2em">What this is not</p>

    <p>
        Escalate is not therapy, and it is not medical, legal or financial advice.
        It writes reflective prose from what you tell it. If you are struggling,
        please talk to a doctor or a crisis line rather than an app.
    </p>
</div>

<p class="small muted center" style="margin-top:var(--s-5)">
    <a href="{{ url()->previous() === url()->current() ? route('login') : url()->previous() }}">Back</a>
</p>
@endsection
