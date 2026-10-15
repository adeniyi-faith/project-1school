@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $band = $b['band'] ?? 'plain';
    $items = $b['items'] ?? [];
@endphp
@if (count($items))
<section class="sec sec--testi {{ $band === 'soft' ? 'sec--soft' : ($band === 'dark' ? 'sec--dark' : '') }}"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading')</div>@endif
    <div class="tsl" data-slider>
        <ul class="tsl-track">
            @foreach ($items as $item)
                <li class="tsl-item">
                    <figure class="tcard">
                        <blockquote><p>{{ $item['text'] }}</p></blockquote>
                        @if (($item['name'] ?? '') !== '' || ($item['role'] ?? '') !== '' || ($item['image'] ?? ''))
                            <figcaption>
                                @if ($item['image'] ?? '')<img src="{{ asset('storage/'.$item['image']) }}" alt="" loading="lazy" decoding="async" width="96" height="96">@endif
                                <span class="tcard-who">@if (($item['name'] ?? '') !== '')<b>{{ $item['name'] }}</b>@endif @if (($item['role'] ?? '') !== '')<span>{{ $item['role'] }}</span>@endif</span>
                            </figcaption>
                        @endif
                    </figure>
                </li>
            @endforeach
        </ul>
        @if (count($items) > 1)
            <div class="tsl-nav" hidden>
                <button type="button" class="tsl-btn" data-dir="-1" aria-label="Previous"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                <span class="tsl-dots">@foreach ($items as $item)<i></i>@endforeach</span>
                <button type="button" class="tsl-btn" data-dir="1" aria-label="Next"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        @endif
    </div>
</div></section>
@endif
