@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $eyebrow = $b['eyebrow'] ?? '';
    $photo = $b['image'] ? asset('storage/'.$b['image']) : asset('images/stock/grove-hero.jpg');
@endphp
<section class="hero hero--grove">
    <div class="gv-hero-photo"><img class="hero-img" src="{{ $photo }}" alt="" fetchpriority="high" decoding="async"></div>
    <div class="wrap"><div class="gv-hero-card">
        @if ($eyebrow !== '')<p class="gv-kick"><i></i>{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::heroHeading($b, true) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
        </div>
    </div></div>
</section>
