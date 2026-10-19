{{--
    Inline SVG icon set (Lucide geometry, 24×24, 1.5 stroke).

    Inline rather than a sprite sheet because these are few, small, and it keeps
    the icon in the same request as the markup that needs it. No emoji is used
    as an icon anywhere in this app.

    Usage: @include('partials.icon', ['name' => 'heart', 'size' => 20])
--}}
@php
    $size = $size ?? 24;
    $paths = match ($name) {
        'sunrise'   => '<path d="M12 2v6M4.93 10.93 6.34 12.34M2 18h2M20 18h2M17.66 12.34l1.41-1.41M22 22H2M8 18a4 4 0 0 1 8 0"/>',
        'book'      => '<path d="M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'compass'   => '<circle cx="12" cy="12" r="9"/><path d="m14.9 9.1-1.4 4.4-4.4 1.4 1.4-4.4z"/>',
        'heart'     => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'rewind'    => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
        'journey'   => '<path d="m8 3 4 8 5-5 5 15H2L8 3z"/>',
        'world'     => '<circle cx="12" cy="12" r="9"/><path d="M3.6 9h16.8M3.6 15h16.8"/><path d="M11.5 3a17 17 0 0 0 0 18M12.5 3a17 17 0 0 1 0 18"/>',
        'plus'      => '<path d="M5 12h14M12 5v14"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-left'  => '<path d="m15 18-6-6 6-6"/>',
        'play'      => '<path d="M6 4.5v15l13-7.5z" fill="currentColor" stroke="none"/>',
        'pause'     => '<path d="M8 4.5h3v15H8zM13 4.5h3v15h-3z" fill="currentColor" stroke="none"/>',
        'loop'      => '<path d="m17 2 4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/>',
        'moon'      => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
        'sparkle'   => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="M12 8.5 13.5 12 12 15.5 10.5 12z"/>',
        'lock'      => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
        'alert'     => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>',
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'   => '<path d="M10.7 5.2A9.6 9.6 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.9 3.8M6.6 6.6A17.2 17.2 0 0 0 2 12s3.6 7 10 7a9.4 9.4 0 0 0 4.4-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="m3 3 18 18"/>',
        'trash'     => '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"/>',
        'edit'      => '<path d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path d="M18.4 2.6a2 2 0 0 1 3 3L12 15l-4 1 1-4Z"/>',
        'download'  => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>',
        'timer'     => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M9 2h6"/>',
        'feather'   => '<path d="M20.2 3.8a5.5 5.5 0 0 0-7.8 0L4 12.2V20h7.8l8.4-8.4a5.5 5.5 0 0 0 0-7.8Z"/><path d="M16 8 2 22M17.5 15H9"/>',
        'quote'     => '<path d="M6 17h2l2-4V7H4v6h3ZM16 17h2l2-4V7h-6v6h3Z"/>',
        default     => '<circle cx="12" cy="12" r="9"/>',
    };
@endphp
<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none"
     stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">{!! $paths !!}</svg>
