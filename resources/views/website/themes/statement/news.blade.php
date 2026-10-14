@extends('website.layout')

@section('main')
<section class="sp-intro"><div class="wrap">
    <p class="sp-kicker">{{ $category ? $label : 'From the school' }}</p>
    <h1>{{ $category ? $categories[$category] : $label }}</h1>
    <nav class="sp-tabs" aria-label="Filter">
        <a href="{{ \App\Website\SiteRenderer::newsUrl($school) }}" @if (! $category) aria-current="page" @endif>All</a>
        @foreach ($categories as $key => $name)
            <a href="{{ \App\Website\SiteRenderer::newsUrl($school, $key) }}" @if ($category === $key) aria-current="page" @endif>{{ $name }}</a>
        @endforeach
    </nav>
</div></section>
<section class="sp-body sp-body--wide"><div class="wrap">
    @if ($posts->isEmpty())
        <p>Nothing here yet. Check back soon.</p>
    @else
        <div class="sp-cards">
            @foreach ($posts as $p)
                <article class="sp-card">
                    @if ($p->cover_image)<a href="{{ \App\Website\SiteRenderer::postUrl($school, $p) }}" tabindex="-1" aria-hidden="true"><img src="{{ \App\Website\SiteRenderer::image($p->cover_image) }}" alt="" loading="lazy" decoding="async" width="640" height="400"></a>@endif
                    <div class="sp-card-body">
                        <p class="sp-kicker">{{ $p->categoryLabel() }}</p>
                        <h2><a href="{{ \App\Website\SiteRenderer::postUrl($school, $p) }}">{{ $p->title }}</a></h2>
                        <p>{{ $p->excerpt ?: \App\Website\Prose::plain($p->body, 160) }}</p>
                        @php $when = $p->category === 'events' && $p->event_date ? $p->event_date : $p->published_at; @endphp
                        @if ($when)<time class="sp-date" datetime="{{ $when->toDateString() }}">{{ $when->format('j F Y') }}</time>@endif
                    </div>
                </article>
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
