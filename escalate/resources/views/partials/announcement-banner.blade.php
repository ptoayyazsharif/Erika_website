{{-- The newest announcement this person has not closed.

     Rendered inside <main>, never as a child of body.app-shell. Adding an
     element to that grid is precisely what turned the desktop layout inside
     out once already: placement there is explicit in CSS now, but content
     belongs in the content column regardless. --}}
@php($announcement = \App\Models\Announcement::bannerFor(auth()->user()))

@if ($announcement)
    <div class="card card-raised announcement" data-enter>
        <div class="row-between wrap" style="gap:var(--s-3);align-items:flex-start">
            <h3 style="margin:0">{{ $announcement->title }}</h3>

            <form method="POST" action="{{ route('announcements.dismiss', $announcement) }}">
                @csrf
                <button class="btn btn-quiet btn-sm" type="submit">Got it</button>
            </form>
        </div>

        {{-- {!! !!} over App\Support\SafeMarkdown output: parser result on
             already-escaped input, never the admin's raw text. --}}
        <div class="announcement-body small">{!! $announcement->html() !!}</div>
    </div>
@endif
