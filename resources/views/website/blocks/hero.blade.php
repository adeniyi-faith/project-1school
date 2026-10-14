@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="hero {{ $b['image'] ? 'hero--photo' : '' }}">
    @if ($b['image'])<img class="hero-img" src="{{ asset('storage/'.$b['image']) }}" alt="" fetchpriority="high" decoding="async">@endif
    <div class="wrap">
        <{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>
        @if ($b['subheading'])<p>{{ $b['subheading'] }}</p>@endif
        <div class="btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
        </div>
    </div>
</section>
