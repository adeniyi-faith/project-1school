@extends('website.layout')

@section('main')
@php
    $meta = null;
    if ($post->published_at && $post->category !== 'events') { $meta = e($post->published_at->format('j F Y')); }
    if ($post->category === 'events') {
        $bits = array_filter([$post->event_date ? $post->event_date->format('l, j F Y') : null, $post->event_place]);
        $meta = e(implode('  |  ', $bits)) ?: null;
    }
@endphp
<article>
    @include('website.themes.warm.head', ['kicker' => $post->categoryLabel(), 'title' => $post->title, 'lead' => $post->excerpt, 'back' => \App\Website\SiteRenderer::newsUrl($school), 'backLabel' => $label, 'meta' => $meta])
    <div class="wa-body"><div class="wrap wa-narrow">
        @if ($post->cover_image)<img class="wa-cover" src="{{ \App\Website\SiteRenderer::image($post->cover_image) }}" alt="{{ $post->title }}" fetchpriority="high" decoding="async" width="1600" height="900">@endif
        <div class="prose">
            {!! \App\Website\Prose::html($post->body) !!}
            <div class="share">
                <span>Share</span>
                <a href="https://wa.me/?text={{ urlencode($post->title.' '.$pageUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp">@include('website.partials.social', ['name' => 'whatsapp'])</a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($pageUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook">@include('website.partials.social', ['name' => 'facebook'])</a>
                <a href="https://x.com/intent/post?url={{ urlencode($pageUrl) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" aria-label="Share on X">@include('website.partials.social', ['name' => 'x'])</a>
            </div>
        </div>
    </div></div>
</article>
@if ($related->isNotEmpty())
<section class="wa-body wa-body--soft"><div class="wrap wa-narrow">
    <p class="wa-kick wa-kick--dark">Keep reading</p>
    <h2 class="wa-h2">More {{ strtolower($label) }}</h2>
    <ul class="wa-list">
        @foreach ($related as $p)
            <li><a href="{{ \App\Website\SiteRenderer::postUrl($school, $p) }}"><span>{{ $p->categoryLabel() }}</span>{{ $p->title }}</a></li>
        @endforeach
    </ul>
</div></section>
@endif
@endsection
