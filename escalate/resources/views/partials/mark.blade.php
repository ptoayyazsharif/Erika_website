{{--
    The doorway E — Erika's artwork.

    Rendered as a background image rather than an <img>, because the file has to
    change with the theme: the mark is a raster, so it cannot take its colour
    from the surface the way the drawn one did. Each palette in app.css names
    its colourway in --mark-url, and this element just reads it. That keeps the
    decision in the stylesheet with the rest of the palette instead of putting a
    theme branch in a Blade template.

    A fixed aspect ratio and explicit box, so the topbar does not jump while the
    image loads. 273:320 is the artwork's own soft bounding box — the letter
    plus the light it throws — measured from the master rather than guessed.

    @param  int|string  $size    height in px (default 28)
    @param  string      $class   extra classes
--}}
@php $markSize = $size ?? 28; @endphp
<span class="mark {{ $class ?? '' }}" aria-hidden="true"
      style="height:{{ $markSize }}px;width:{{ round($markSize * 273 / 320, 2) }}px"></span>
