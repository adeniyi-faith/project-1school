@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $eyebrow = $b['eyebrow'] ?? ''; @endphp
<section class="hero {{ $b['image'] ? 'hero--photo' : 'hero--plain' }}">
    @if ($b['image'])<img class="hero-img" src="{{ asset('storage/'.$b['image']) }}" alt="" fetchpriority="high" decoding="async">@endif
    <div class="wrap">
        @if ($eyebrow !== '')<p class="hero-tag"><i></i>{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::accent($b["heading"]) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
        </div>
    </div>
</section>
