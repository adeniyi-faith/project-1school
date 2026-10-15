@extends('website.layout')

@section('main')
@include('website.themes.warm.head', ['kicker' => $category ? $label : 'From the school', 'title' => $category ? $categories[$category] : $label])
<section class="wa-body"><div class="wrap">
    <nav class="wa-tabs" aria-label="Filter">
        <a href="{{ \App\Website\SiteRenderer::newsUrl($school) }}" @if (! $category) aria-current="page" @endif>All</a>
        @foreach ($categories as $key => $name)
            <a href="{{ \App\Website\SiteRenderer::newsUrl($school, $key) }}" @if ($category === $key) aria-current="page" @endif>{{ $name }}</a>
        @endforeach
    </nav>
    @if ($posts->isEmpty())
        <p>Nothing here yet. Check back soon.</p>
    @else
        <div class="wa-cards">
            @foreach ($posts as $p)
                @php $when = $p->category === 'events' && $p->event_date ? $p->event_date : $p->published_at; @endphp
                <article class="wa-card">
                    @if ($p->cover_image)<a href="{{ \App\Website\SiteRenderer::postUrl($school, $p) }}" tabindex="-1" aria-hidden="true"><img src="{{ \App\Website\SiteRenderer::image($p->cover_image) }}" alt="" loading="lazy" decoding="async" width="640" height="400"></a>@endif
                    <div class="wa-card-body">
                        <p class="wa-kick">{{ $p->categoryLabel() }}</p>
                        <h2><a href="{{ \App\Website\SiteRenderer::postUrl($school, $p) }}">{{ $p->title }}</a></h2>
                        <p>{{ $p->excerpt ?: \App\Website\Prose::plain($p->body, 150) }}</p>
                        @if ($when)<time datetime="{{ $when->toDateString() }}">{{ $when->format('j M Y') }}</time>@endif
                    </div>
                </article>
            @endforeach
        </div>
        @if ($posts->hasPages())
            <nav class="pager" aria-label="More pages">
                @if ($posts->previousPageUrl())<a class="btn btn--solid" href="{{ $posts->previousPageUrl() }}" rel="prev">Newer</a>@endif
                <span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>
                @if ($posts->nextPageUrl())<a class="btn btn--solid" href="{{ $posts->nextPageUrl() }}" rel="next">Older</a>@endif
            </nav>
        @endif
    @endif
</div></section>
@endsection
