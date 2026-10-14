@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $items = $news->when(($b['category'] ?? 'all') !== 'all', fn ($c) => $c->where('category', $b['category']))->take($b['count'])->values();
@endphp
@if ($items->isNotEmpty() || $preview)
<section class="sec"><div class="wrap">
    <div class="sec-head sec-head--row">
        @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
        @if ($newsUrl)<a class="more" href="{{ $newsUrl }}">See all &rarr;</a>@endif
    </div>
    @if ($items->isEmpty())
        <p>No news yet. Check back soon.</p>
    @else
        <div class="news {{ $items->count() >= 3 ? 'news--feature' : 'news--grid' }}">
            @foreach ($items as $item)@include('website.partials.news-card', ['item' => $item])@endforeach
        </div>
    @endif
</div></section>
@endif
