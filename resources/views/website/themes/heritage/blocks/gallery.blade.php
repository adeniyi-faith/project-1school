@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $shown = $galleryPhotos->take($b['count'] ?? 8)->all(); @endphp
@if (count($shown) || $preview)
<section class="sec sec--headlines"><div class="wrap">
    @include('website.partials.heading')
    @if (count($shown))
        @include('website.partials.photo-grid', ['photos' => $shown])
        <p class="more-row"><a class="btn btn--ghost" href="{{ \App\Website\SiteRenderer::galleryUrl($school) }}">See all photos</a></p>
    @else
        <p>Photos will show here once you add them in Website &gt; Gallery.</p>
    @endif
</div></section>
@endif
