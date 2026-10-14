@extends('website.layout')

@section('main')
<article>
    <header class="sp-intro"><div class="wrap sp-narrow">
        <a class="sp-back" href="{{ \App\Website\SiteRenderer::newsUrl($school) }}">&larr; {{ $label }}</a>
        <p class="sp-kicker">{{ $post->categoryLabel() }}</p>
        <h1>{{ $post->title }}</h1>
        @if ($post->excerpt)<p class="sp-lead">{{ $post->excerpt }}</p>@endif
        @if ($post->published_at && $post->category !== 'events')<time class="sp-date" datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('j F Y') }}</time>@endif
        @if ($post->category === 'events' && ($post->event_date || $post->event_place))
            <dl class="sp-facts">
                @if ($post->event_date)<div><dt>Date</dt><dd>{{ $post->event_date->format('l, j F Y') }}</dd></div>@endif
                @if ($post->event_place)<div><dt>Venue</dt><dd>{{ $post->event_place }}</dd></div>@endif
            </dl>
        @endif
    </div></header>
    @if ($post->cover_image)
        <div class="wrap sp-narrow"><img class="sp-cover" src="{{ \App\Website\SiteRenderer::image($post->cover_image) }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async" width="1600" height="900"></div>
    @endif
    <div class="sp-body"><div class="wrap sp-narrow prose">
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
<section class="sp-band"><div class="wrap">
    <p class="sp-kicker">Keep reading</p>
    <h2>More {{ strtolower($label) }}</h2>
    <ul class="sp-more">
        @foreach ($related as $p)
            <li><a href="{{ \App\Website\SiteRenderer::postUrl($school, $p) }}"><span>{{ $p->categoryLabel() }}</span>{{ $p->title }}</a></li>
        @endforeach
    </ul>
</div></section>
@endif
@endsection
