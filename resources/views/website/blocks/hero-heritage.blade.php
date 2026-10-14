@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $eyebrow = $b['eyebrow'] ?? ''; @endphp
<section class="hh {{ $b['image'] ? 'hh--photo' : 'hh--plain' }}">
    @if ($b['image'])<img class="hh-img" src="{{ asset('storage/'.$b['image']) }}" alt="" fetchpriority="high" decoding="async">@endif
    <div class="wrap hh-in">
        @if ($eyebrow !== '')<p class="hh-tag">{{ $eyebrow }}</p>@endif
        <{{ $tag }}>{!! \App\Website\Prose::accent($b["heading"]) !!}</{{ $tag }}>
        @if ($b['subheading'])<p class="hh-sub">{{ $b['subheading'] }}</p>@endif
        <div class="hh-btns">
            @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'ghost'])
            @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
        </div>
    </div>
</section>
