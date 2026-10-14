@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
@endphp
<section class="sec sec--soft"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <div class="cards cards--pillars">
        @foreach ($b['items'] as $item)
            <article class="card pillar">
                @if ($item['image'] ?? '')<img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="600" height="400">@endif
                <div class="card-body">
                    <p class="pillar-n">{{ $roman[$loop->index] ?? $loop->iteration }}</p>
                    <h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                </div>
            </article>
        @endforeach
    </div>
</div></section>
