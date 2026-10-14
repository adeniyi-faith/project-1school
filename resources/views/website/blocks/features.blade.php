@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $band = $b['band'] ?? 'soft';
    $style = $b['style'] ?? 'cards';
    $pillars = $style === 'pillars';
    $panels = $style === 'panels';
    $word = trim($b['tag'] ?? ($pillars ? 'Pillar' : ''));
    $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
@endphp
<section class="sec {{ $band === 'soft' ? 'sec--soft' : ($band === 'dark' ? 'sec--dark' : '') }}"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <div class="cards {{ $pillars ? 'cards--pillars' : ($panels ? 'cards--panels' : '') }}">
        @foreach ($b['items'] as $item)
            @php $icon = $item['icon'] ?? 'none'; @endphp
            @if ($pillars)
                <article class="card pillar">
                    @if ($item['image'] ?? '')<img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="600" height="400">@endif
                    <div class="card-body">
                        <p class="pillar-n">{{ trim($word.' '.($roman[$loop->index] ?? $loop->iteration)) }}</p>
                        <h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                    </div>
                </article>
            @elseif ($panels)
                <article class="card panel-card">
                    <div class="card-body"><h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif</div>
                </article>
            @else
                <article class="card {{ ($item['image'] ?? '') || $icon !== 'none' ? '' : 'card--plain' }}">
                    @if ($item['image'] ?? '')<img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="600" height="400">@endif
                    <div class="card-body">
                        @if ($icon !== 'none')<span class="card-ico">@include('website.partials.icon', ['name' => $icon])</span>@endif
                        <h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                    </div>
                </article>
            @endif
        @endforeach
    </div>
</div></section>
