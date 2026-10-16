@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $pic = $b['image'] !== '';
    $band = $b['band'] ?? 'plain';
    // on an inner page the first heading opens a cream title area with a breadcrumb, and the words follow in the same area
    $banner = ($b['_h1'] ?? false) && ! in_array($current ?? 'home', ['home', 'search', 'news', 'gallery'], true) && isset($page);
@endphp
@if ($banner)
<section class="sec lf-title"><div class="wrap">
    <nav class="lf-crumbs" aria-label="Breadcrumb"><a href="{{ $homeUrl }}">Home</a><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg><span aria-current="page">{{ $page->title }}</span></nav>
    <div class="{{ $pic ? 'split split--'.$b['side'] : 'prose lf-title-body' }}">
        <div class="{{ $pic ? 'prose' : '' }}">
            @include('website.partials.heading')
            {!! \App\Website\Prose::html($b['body'], 3) !!}
        </div>
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
