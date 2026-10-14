@extends('website.layout')

@section('main')
<section class="sec hp-head"><div class="wrap">
    <h1>{{ $category ? $categories[$category] : $label }}</h1>
    <nav class="hp-filter" aria-label="Filter">
        <a href="{{ \App\Website\SiteRenderer::newsUrl($school) }}" @if (! $category) aria-current="page" @endif>All</a>
        @foreach ($categories as $key => $name)
            <a href="{{ \App\Website\SiteRenderer::newsUrl($school, $key) }}" @if ($category === $key) aria-current="page" @endif>{{ $name }}</a>
        @endforeach
    </nav>
</div></section>
<section class="sec sec--tight"><div class="wrap">
    @if ($posts->isEmpty())
        <p>Nothing here yet. Check back soon.</p>
    @else
        <div class="news news--stack news--lead">
            @foreach ($posts as $p)
                @include('website.partials.news-card', ['item' => [
                    'title' => $p->title, 'url' => \App\Website\SiteRenderer::postUrl($school, $p), 'excerpt' => $p->excerpt ?: \App\Website\Prose::plain($p->body, 180),
                    'date' => $p->category === 'events' && $p->event_date ? $p->event_date : $p->published_at, 'image' => \App\Website\SiteRenderer::image($p->cover_image), 'tag' => $p->categoryLabel(),
                ]])
            @endforeach
        </div>
        @if ($posts->hasPages())
            <nav class="pager" aria-label="More pages">
                @if ($posts->previousPageUrl())<a class="btn btn--ghost" href="{{ $posts->previousPageUrl() }}" rel="prev">Newer</a>@endif
                <span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>
                @if ($posts->nextPageUrl())<a class="btn btn--ghost" href="{{ $posts->nextPageUrl() }}" rel="next">Older</a>@endif
            </nav>
        @endif
    @endif
</div></section>
@endsection
