@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $eyebrow = $b['eyebrow'] ?? ''; @endphp
<section class="hero hero--warm">
    <span class="blob blob--1" aria-hidden="true"></span><span class="blob blob--2" aria-hidden="true"></span><span class="blob blob--3" aria-hidden="true"></span>
    <div class="wrap">
        @if ($eyebrow !== '')<p class="hero-tag"><i></i>{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::accent($b['heading']) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
        </div>
        @if ($b['image'])<div class="hero-arch"><img src="{{ asset('storage/'.$b['image']) }}" alt="" fetchpriority="high" decoding="async"></div>@endif
    </div>
    <svg class="wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path d="M0 40c240 60 480 60 720 20s480-50 720 10v20H0z"/></svg>
</section>
