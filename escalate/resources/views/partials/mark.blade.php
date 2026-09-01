{{--
    The doorway E, inline.

    Inline rather than <img src="/brand/mark.svg"> for one reason: the letter is
    painted in currentColor, so a single copy serves ivory-on-aubergine and
    aubergine-on-ivory without a second file or a filter. Only the light through
    the door is a fixed colour, and that is the point of it.

    The viewBox is cropped to the mark's own ink (x 114-410, y 70-466). The
    source file's 512 box is padding meant for icon export; used inline it would
    leave the letter floating away from whatever sits beside it.

    Gradient ids are suffixed, because two marks on one page sharing an id means
    the second one silently paints with the first one's gradient.

    @param  int|string  $size    height in px (default 28)
    @param  string      $class   extra classes
--}}
@php
    $markSize = $size ?? 28;
    $markId = 'mk'.substr(md5(uniqid('', true)), 0, 6);
@endphp
<svg class="mark {{ $class ?? '' }}" viewBox="114 70 296 396" height="{{ $markSize }}"
     width="{{ round($markSize * 296 / 396, 2) }}" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="{{ $markId }}l" x1="0" y1="1" x2="0.3" y2="0">
            <stop offset="0%" stop-color="#4966B3"/>
            <stop offset="50%" stop-color="#7456C7"/>
            <stop offset="100%" stop-color="#9A7CF0"/>
        </linearGradient>
        <radialGradient id="{{ $markId }}s" cx="0.5" cy="0" r="1">
            <stop offset="0%" stop-color="#9A7CF0" stop-opacity="0.45"/>
            <stop offset="55%" stop-color="#7456C7" stop-opacity="0.13"/>
            <stop offset="100%" stop-color="#4966B3" stop-opacity="0"/>
        </radialGradient>
    </defs>
    <g fill="currentColor">
        <path d="M120 76 h56 v360 h-56 z"/>
        <path d="M120 76 h248 v48 h-248 z"/>
        <path d="M120 196 h192 v44 h-192 z"/>
        <path d="M120 388 h224 l60 -20 v46 l-60 20 h-224 z"/>
    </g>
    <path d="M220 388 v-68 a42 42 0 0 1 84 0 v68 z" fill="url(#{{ $markId }}l)"/>
    <path d="M220 388 h84 l38 72 h-160 z" fill="url(#{{ $markId }}s)"/>
</svg>
