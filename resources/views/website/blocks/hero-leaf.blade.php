@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $eyebrow = $b['eyebrow'] ?? '';
    $photo = $b['image'] ? asset('storage/'.$b['image']) : asset('images/stock/grove-boys.jpg');
    $photo2 = asset('images/stock/grove-writing.jpg');
@endphp
<section class="hero hero--leaf">
    <div class="wrap lf-hero-in">
        <div class="lf-hero-text">
            @if ($eyebrow !== '')<p class="eyebrow">{{ $eyebrow }}</p>@endif
            <{{ $tag }}>{!! \App\Website\Prose::heroHeading($b, true) !!}</{{ $tag }}>
            @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
            <div class="btns">
                @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
                @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
            </div>
        </div>
        <div class="lf-leaves" aria-hidden="false">
            <figure class="lf-leaf lf-leaf--a"><img src="{{ $photo }}" alt="" fetchpriority="high" decoding="async" width="660" height="720"></figure>
            <figure class="lf-leaf lf-leaf--b"><img src="{{ $photo2 }}" alt="" loading="lazy" decoding="async" width="540" height="830"></figure>
        </div>
    </div>
    <svg class="lf-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70C180 18 360 14 560 46s420 76 620 40c110-20 200-52 260-70V120H0Z"/></svg>
</section>
