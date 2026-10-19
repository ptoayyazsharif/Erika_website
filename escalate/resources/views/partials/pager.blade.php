{{--
    Pagination, in this app's own vocabulary.

    Blade's $paginator->links() renders Laravel's default view, which is styled
    entirely in Tailwind utility classes. Nothing in this app is: the CSS is
    hand-authored custom properties in public/css/app.css and there is no build
    step, so every one of those classes resolves to nothing. The result on My
    Stories, the moment anyone owns more than twenty readings, is a row of bare
    unstyled links and two raw SVG chevrons sitting under the cards.

    So it is drawn here instead, out of .btn and .small — classes that exist.
    Newest-first ordering makes previous/next the honest controls; a numbered
    strip would be pure decoration on a list nobody navigates by page number.

    Usage: @include('partials.pager', ['paginator' => $stories])
--}}
@if ($paginator->hasPages())
    <nav class="row-between" style="margin-top:var(--s-6);gap:var(--s-3)" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span></span>
        @else
            <a class="btn btn-quiet btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                @include('partials.icon', ['name' => 'chevron-left', 'size' => 16]) Newer
            </a>
        @endif

        <span class="small faint">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-quiet btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">
                Older @include('partials.icon', ['name' => 'chevron-right', 'size' => 16])
            </a>
        @else
            <span></span>
        @endif
    </nav>
@endif
