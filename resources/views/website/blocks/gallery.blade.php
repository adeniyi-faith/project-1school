@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $shown = $galleryPhotos->take($b['count'] ?? 8)->all(); @endphp
@if (count($shown) || $preview)
<section class="sec"><div class="wrap">
    <div class="sec-head sec-head--row">
        <div>@include('website.partials.heading')</div>
        @if (count($shown))<a class="more" href="{{ \App\Website\SiteRenderer::galleryUrl($school) }}">See all photos &rarr;</a>@endif
    </div>
    @if (count($shown))
        @include('website.partials.photo-grid', ['photos' => $shown])
    @else
        <p>Photos will show here once you add them in Website &gt; Gallery.</p>
    @endif
</div></section>
@endif
