@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $eyebrow = $b['eyebrow'] ?? '';
    $photo = $b['image'] ? asset('storage/'.$b['image']) : asset('images/stock/classroom-reading.jpg');
    $applyUrl = $site->option('apply_button') ? route('public.admission.show', $school) : null;
@endphp
<section class="hero hero--warm">
    <img class="hero-img" src="{{ $photo }}" alt="" fetchpriority="high" decoding="async">
    <div class="wrap hero-in">
        @if ($eyebrow !== '')<p class="hero-kick"><span>{{ $eyebrow }}</span></p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::heroHeading($b) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'solid'])
        </div>
    </div>
    @if ($applyUrl)<a class="hero-tab" href="{{ $applyUrl }}"><span>Apply</span><i aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h13m-5-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></i></a>@endif
</section>
