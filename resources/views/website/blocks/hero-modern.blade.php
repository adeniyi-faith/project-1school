@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $eyebrow = $b['eyebrow'] ?? '';
    $photo = $b['image'] ? asset('storage/'.$b['image']) : asset('images/stock/classroom-reading.jpg');
@endphp
<section class="hero hero--modern">
    <img class="hero-img" src="{{ $photo }}" alt="" fetchpriority="high" decoding="async">
    <div class="wrap hero-in">
        @if ($eyebrow !== '')<p class="hero-kick">{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::heroHeading($b) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'solid'])
        </div>
        <a class="hero-down" href="#after-hero" aria-label="Scroll down"><svg viewBox="0 0 40 70" aria-hidden="true"><path d="M20 2v64M3 48l17 18 17-18" fill="none" stroke="currentColor" stroke-width="2"/></svg></a>
    </div>
    <span class="hero-cut" aria-hidden="true"></span>
</section>
<span id="after-hero"></span>
