@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $pic = $b['image'] !== '';
    $band = $b['band'] ?? 'plain';
    // on an inner page the first heading becomes a title banner with a photo, and the words follow below it
    $banner = ($b['_h1'] ?? false) && ! in_array($current ?? 'home', ['home', 'search', 'news', 'gallery'], true) && isset($page);
    $bg = ['grove-classroom', 'grove-writing', 'grove-window', 'grove-friends'][crc32((string) ($current ?? '')) % 4];
@endphp
@if ($banner)
<section class="gv-title" style="--gv-photo:url('{{ asset('images/stock/'.$bg.'.jpg') }}')"><div class="wrap">
    <nav class="gv-crumbs" aria-label="Breadcrumb"><a href="{{ $homeUrl }}">Home</a><span aria-hidden="true">/</span><span>{{ $page->title }}</span></nav>
    @if (($b['eyebrow'] ?? '') !== '')<p class="eyebrow">{{ $b['eyebrow'] }}</p>@endif
    <h1>{{ $b['heading'] }}</h1>
</div></section>
<section class="sec gv-after-title {{ $band === 'soft' ? 'sec--soft' : '' }}"><div class="wrap">
    <div class="{{ $pic ? 'split split--'.$b['side'] : 'prose' }}">
        <div class="{{ $pic ? 'prose' : '' }}">{!! \App\Website\Prose::html($b['body'], 3) !!}</div>
        @if ($pic)<img src="{{ asset('storage/'.$b['image']) }}" alt="{{ $b['heading'] }}" loading="lazy" decoding="async" width="800" height="600">@endif
    </div>
</div></section>
@else
<section class="sec {{ $band === 'soft' ? 'sec--soft' : ($band === 'dark' ? 'sec--dark' : '') }}"><div class="wrap">
    <div class="{{ $pic ? 'split split--'.$b['side'] : ($b['align'] === 'center' ? 'center prose' : 'prose') }}">
        <div class="{{ $pic ? 'prose' : '' }}">
            @include('website.partials.heading')
            {!! \App\Website\Prose::html($b['body'], 3) !!}
        </div>
        @if ($pic)<img src="{{ asset('storage/'.$b['image']) }}" alt="{{ $b['heading'] }}" loading="lazy" decoding="async" width="800" height="600">@endif
    </div>
</div></section>
@endif
