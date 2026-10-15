@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $eyebrow = $b['eyebrow'] ?? '';
    $photo = $b['image'] ? asset('storage/'.$b['image']) : asset('images/stock/classroom-reading.jpg');
@endphp
<section class="hero hero--evergreen">
    <img class="hero-img" src="{{ $photo }}" alt="" fetchpriority="high" decoding="async">
    <div class="eg-hero-panel"><div class="wrap">
        @if ($eyebrow !== '')<p class="eg-kick">{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::accent($b['heading']) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
        </div>
    </div></div>
</section>
