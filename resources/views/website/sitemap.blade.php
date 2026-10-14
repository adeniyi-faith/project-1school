<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($pages as $p)
    <url><loc>{{ \App\Website\SiteRenderer::pageUrl($school, $p) }}</loc><lastmod>{{ $p->updated_at->toAtomString() }}</lastmod></url>
@endforeach
@if ($posts->isNotEmpty())
    <url><loc>{{ \App\Website\SiteRenderer::newsUrl($school) }}</loc><lastmod>{{ $posts->first()->published_at->toAtomString() }}</lastmod></url>
@endif
@foreach ($posts as $p)
    <url><loc>{{ \App\Website\SiteRenderer::postUrl($school, $p) }}</loc><lastmod>{{ ($p->updated_at ?? $p->published_at)->toAtomString() }}</lastmod></url>
@endforeach
</urlset>
