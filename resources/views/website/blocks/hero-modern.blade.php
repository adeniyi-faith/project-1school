@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $eyebrow = $b['eyebrow'] ?? ''; @endphp
<section class="hero hero--modern">
    <div class="wrap hero-grid">
        <div class="hero-copy">
            @if ($eyebrow !== '')<p class="hero-tag"><i></i>{{ $eyebrow }}</p>@endif
            <{{ $tag }}>{!! \App\Website\Prose::accent($b['heading'], true) !!}</{{ $tag }}>
            @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
            <div class="btns">
                @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
                @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
            </div>
        </div>
        <div class="hero-art" aria-hidden="true">
            @if ($b['image'])
                <img src="{{ asset('storage/'.$b['image']) }}" alt="" fetchpriority="high" decoding="async">
            @else
                <span class="art-a"></span><span class="art-b"></span><span class="art-c"></span>
                <span class="art-chip art-chip--1">@include('website.partials.icon', ['name' => 'cap'])</span>
                <span class="art-chip art-chip--2">@include('website.partials.icon', ['name' => 'book'])</span>
                <span class="art-chip art-chip--3">@include('website.partials.icon', ['name' => 'star'])</span>
            @endif
        </div>
    </div>
</section>
