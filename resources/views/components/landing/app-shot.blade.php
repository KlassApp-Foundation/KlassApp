@props(['src', 'alt', 'title', 'url'])
{{-- Landing app screenshot in a browser-chrome frame. The frame is a fixed 16:10 box
     (.shot-frame), so a replacement capture at a slightly different size can't reflow
     the grid. Name each capture after the screen it shows (app-fees.webp, not app-page.webp). --}}
<figure {{ $attributes->class(['shot']) }}>
  <div class="ui-chrome"><span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-dot"></span><span class="ui-chrome-title">{{ $title }}</span><span class="shot-url">{{ $url }}</span></div>
  <div class="shot-frame"><img src="{{ $src }}" alt="{{ $alt }}" width="1600" height="1000" loading="lazy" decoding="async"></div>
</figure>
