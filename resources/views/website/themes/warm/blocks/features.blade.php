@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $style = $b['style'] ?? 'cards';
    $pillars = $style === 'pillars';
    $word = trim($b['tag'] ?? ($pillars ? 'Pillar' : ''));
    $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
@endphp
<section class="sec {{ ($b['band'] ?? 'soft') === 'soft' ? 'sec--soft' : '' }}"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <div class="wa-tiles">
        @foreach ($b['items'] as $item)
            @php $icon = $item['icon'] ?? 'none'; @endphp
            <article class="wa-tile">
                @if ($item['image'] ?? '')<img src="{{ asset('storage/'.$item['image']) }}" alt="" loading="lazy" decoding="async" width="600" height="400">@endif
                <div class="wa-tile-body">
                    @if ($pillars)<p class="wa-num">{{ trim($word.' '.($roman[$loop->index] ?? $loop->iteration)) }}</p>@elseif ($icon !== 'none')<span class="wa-ico">@include('website.partials.icon', ['name' => $icon])</span>@endif
                    <h3>{{ $item['title'] }}</h3>
                    @if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                </div>
            </article>
        @endforeach
    </div>
</div></section>
