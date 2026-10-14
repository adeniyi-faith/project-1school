@extends('website.layout')

@section('main')
<article>
    <header class="sec page-head post-head"><div class="wrap narrow">
        <a class="crumb" href="{{ \App\Website\SiteRenderer::newsUrl($school) }}">&larr; {{ $label }}</a>
        <span class="tag">{{ $post->categoryLabel() }}</span>
        <h1>{{ $post->title }}</h1>
        @if ($post->excerpt)<p class="lede">{{ $post->excerpt }}</p>@endif
        @if ($post->published_at && $post->category !== 'events')<time class="date" datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('M j, Y') }}</time>@endif
        @if ($post->category === 'events' && ($post->event_date || $post->event_place))
            <dl class="event-box">
                @if ($post->event_date)<div><dt>When</dt><dd>{{ $post->event_date->format('l j F Y') }}</dd></div>@endif
                @if ($post->event_place)<div><dt>Where</dt><dd>{{ $post->event_place }}</dd></div>@endif
            </dl>
        @endif
    </div></header>
    @if ($post->cover_image)
        <div class="wrap"><img class="cover" src="{{ \App\Website\SiteRenderer::image($post->cover_image) }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async" width="1600" height="900"></div>
    @endif
    <div class="sec sec--tight"><div class="wrap narrow prose">
        {!! \App\Website\Prose::html($post->body) !!}
        <div class="share">
            <span>Share</span>
            <a href="https://wa.me/?text={{ urlencode($post->title.' '.$pageUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp">@include('website.partials.social', ['name' => 'whatsapp'])</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($pageUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook">@include('website.partials.social', ['name' => 'facebook'])</a>
            <a href="https://x.com/intent/post?url={{ urlencode($pageUrl) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" aria-label="Share on X">@include('website.partials.social', ['name' => 'x'])</a>
        </div>
    </div></div>
</article>
@if ($related->isNotEmpty())
<section class="sec sec--soft"><div class="wrap">
    <div class="sec-head"><h2>More {{ strtolower($label) }}</h2></div>
    <div class="news news--grid">
        @foreach ($related as $p)
            @include('website.partials.news-card', ['item' => [
                'title' => $p->title, 'url' => \App\Website\SiteRenderer::postUrl($school, $p), 'excerpt' => $p->excerpt ?: \App\Website\Prose::plain($p->body, 140),
                'date' => $p->category === 'events' && $p->event_date ? $p->event_date : $p->published_at, 'image' => \App\Website\SiteRenderer::image($p->cover_image), 'tag' => $p->categoryLabel(),
            ]])
        @endforeach
    </div>
</div></section>
@endif
@endsection
