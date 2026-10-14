@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $items = $news->when(($b['category'] ?? 'all') !== 'all', fn ($c) => $c->where('category', $b['category']))->take($b['count'])->values();
@endphp
@if ($items->isNotEmpty() || $preview)
<section class="sec sec--headlines"><div class="wrap">
    @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
    @if ($items->isEmpty())
        <p>No news yet. Check back soon.</p>
    @else
        <div class="news news--stack">
            @foreach ($items as $item)@include('website.partials.news-card', ['item' => $item])@endforeach
        </div>
        @if ($newsUrl)<p class="more-row"><a class="btn btn--ghost" href="{{ $newsUrl }}">Visit {{ $site->option('news_label') }}</a></p>@endif
    @endif
</div></section>
@endif
