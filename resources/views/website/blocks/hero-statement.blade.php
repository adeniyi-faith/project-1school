@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $facts = $b['facts'] ?? [];
    $hasCard = count($facts) > 0;
@endphp
<section class="hero hero--statement {{ ($b['image'] ?? '') ? 'hero--photo' : '' }} {{ $hasCard ? 'hero--card' : '' }}">
    @if ($b['image'] ?? '')<img class="hero-img" src="{{ asset('storage/'.$b['image']) }}" alt="" fetchpriority="high" decoding="async">@endif
    <div class="wrap hero-grid">
        <div class="hero-copy">
            @if (($b['eyebrow'] ?? '') !== '')<p class="hero-kicker">{{ $b['eyebrow'] }}</p>@endif
            <{{ $tag }}>{!! \App\Website\Prose::accent($b['heading'], true) !!}</{{ $tag }}>
            @if (($b['lead'] ?? '') !== '')<p class="hero-lead">{{ $b['lead'] }}</p>@endif
            @if ($b['subheading'])<p class="hero-sub">{{ $b['subheading'] }}</p>@endif
            @if (! $hasCard)
                <div class="btns">
                    @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
                    @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
                </div>
            @endif
            <div class="hero-foot" aria-hidden="{{ ($b['foot'] ?? '') === '' ? 'true' : 'false' }}">
                <p class="hero-dots" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i class="on"></i><i></i><i></i><i></i><i></i><i></i></p>
                @if (($b['foot'] ?? '') !== '')<p class="hero-motto">{{ $b['foot'] }}</p>@endif
            </div>
        </div>
        @if ($hasCard)
            <aside class="hero-card">
                @foreach ($facts as $fact)
                    <div class="fact"><p class="fact-l">{{ $fact['label'] }}</p><p class="fact-v">{!! str_replace('|', '<br>', e($fact['value'])) !!}</p></div>
                @endforeach
                <div class="btns">
                    @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
                    @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'ghost'])
                </div>
            </aside>
        @endif
    </div>
</section>
