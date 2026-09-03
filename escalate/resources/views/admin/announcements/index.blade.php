@extends('layouts.app', ['title' => 'Announcements'])

@section('content')
<div class="page-head" data-enter-hero>
    <p class="eyebrow">Admin</p>
    <h1>Announcements</h1>
    <p class="lede">
        Something to tell everybody — in the app, by email, or both. Write it
        once and choose where it goes.
    </p>
</div>

@include('admin._nav')

<div class="card" data-enter>
    <h3 style="margin:0 0 var(--s-4)">Write one</h3>

    <form method="POST" action="{{ route('admin.announcements.store') }}" data-once>
        @csrf

        <div class="field">
            <label for="title">Title</label>
            <input class="input" id="title" name="title" maxlength="120" required
                   value="{{ old('title') }}">
        </div>

        <div class="field">
            <label for="body">Message</label>
            <span class="hint">
                Markdown. Blank lines make paragraphs, **stars** make bold.
            </span>
            <textarea class="textarea" id="body" name="body" rows="5" maxlength="4000"
                      required>{{ old('body') }}</textarea>
        </div>

        <label class="option {{ old('show_in_app', true) ? 'is-on' : '' }}">
            <input type="checkbox" name="show_in_app" value="1" @checked(old('show_in_app', true))>
            <span class="tick" aria-hidden="true"></span>
            <span class="option-body">
                <span class="option-label">Show it in the app</span>
                <small>A banner at the top, until each person closes it.</small>
            </span>
        </label>

        <label class="option {{ old('send_email') ? 'is-on' : '' }}" style="margin-bottom:var(--s-5)">
            <input type="checkbox" name="send_email" value="1" @checked(old('send_email'))>
            <span class="tick" aria-hidden="true"></span>
            <span class="option-body">
                <span class="option-label">Email it to everyone</span>
                <small>
                    Ticking this does not send it. You press Send afterwards, once,
                    and it goes to the {{ $audience }}
                    {{ Str::plural('person', $audience) }} who have not opted out.
                </small>
            </span>
        </label>

        <button class="btn btn-full" type="submit" data-busy="Saving…">Write it</button>
    </form>
</div>

<div class="rule">Already said</div>

@forelse ($announcements as $announcement)
    <div class="card {{ $announcement->show_in_app ? '' : 'card-quiet' }}" data-enter>
        <div class="row-between wrap" style="gap:var(--s-3);align-items:flex-start">
            <div>
                <h3 style="margin:0">{{ $announcement->title }}</h3>
                <p class="small faint" style="margin:var(--s-1) 0 0">
                    {{ $announcement->published_at?->diffForHumans() ?? 'draft' }}
                    @if ($announcement->author) · {{ $announcement->author->name }} @endif
                    · {{ $announcement->dismissals_count }} closed it
                </p>
            </div>

            <div class="row wrap" style="gap:var(--s-2)">
                @if ($announcement->show_in_app)<span class="pill">in the app</span>@endif
                @if ($announcement->wasEmailed())
                    <span class="pill pill-manifested">emailed</span>
                @elseif ($announcement->send_email)
                    <span class="pill pill-unfolding">not sent yet</span>
                @endif
            </div>
        </div>

        <div class="announcement-body small muted" style="margin-top:var(--s-3)">
            {!! $announcement->html() !!}
        </div>

        <div class="row wrap" style="gap:var(--s-2);margin-top:var(--s-4)">
            @unless ($announcement->wasEmailed())
                <form method="POST" action="{{ route('admin.announcements.send', $announcement) }}"
                      data-confirm="Email this to every person who has not opted out? This cannot be taken back.">
                    @csrf
                    <button class="btn btn-quiet btn-sm" type="submit" data-busy="Queueing…">
                        Send it by email
                    </button>
                </form>
            @endunless

            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}"
                  data-confirm="Delete this announcement? It disappears from the app. Any email already sent has been sent.">
                @csrf @method('DELETE')
                <button class="btn btn-quiet btn-sm" type="submit" style="color:var(--danger)">Delete</button>
            </form>
        </div>
    </div>
@empty
    <div class="card card-quiet" data-enter>
        <p class="small muted" style="margin:0">Nothing said yet.</p>
    </div>
@endforelse
@endsection
