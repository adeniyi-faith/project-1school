@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $eyebrow = $b['eyebrow'] ?? '';
    $photo = $b['image'] ? asset('storage/'.$b['image']) : null;
    $btns = '';
@endphp
<section class="hero hero--crest">
    <i class="cr-chev" aria-hidden="true"></i>
    <i class="cr-ring" aria-hidden="true"></i>
    <div class="wrap cr-in">
        @if ($eyebrow !== '')<p class="cr-kick">{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::heroHeading($b) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
        @if ($photo)
            <div class="btns">
                @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
                @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
            </div>
        @endif
    </div>
    <i class="cr-arrow" aria-hidden="true"></i>
    <div class="cr-base">
        @if ($photo)
            <img src="{{ $photo }}" alt="" fetchpriority="high" decoding="async">
        @else
            <div class="btns">
                @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
                @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
            </div>
        @endif
    </div>
</section>
