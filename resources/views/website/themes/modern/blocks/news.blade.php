@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $items = $news->when(($b['category'] ?? 'all') !== 'all', fn ($c) => $c->where('category', $b['category']))->take($b['count'])->values();
@endphp
@if ($items->isNotEmpty() || $preview)
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    @if ($items->isEmpty())
        <p>No news yet. Check back soon.</p>
    @else
        <div class="mo-stories">
            @foreach ($items as $item)
                <article class="mo-story">
                    @if ($item['image'])<img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async" width="640" height="640">@endif
                    <p class="eyebrow">{{ $item['tag'] }}</p>
                    <h3>@if ($item['url'])<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@else{{ $item['title'] }}@endif</h3>
                    @if ($item['excerpt'])<p>{{ $item['excerpt'] }}</p>@endif
                    @if ($item['date'])<time class="date" datetime="{{ $item['date']->toDateString() }}">{{ $item['date']->format('j M Y') }}</time>@endif
                </article>
            @endforeach
        </div>
        @if ($newsUrl)<p class="mo-more"><a class="btn btn--solid" href="{{ $newsUrl }}">See all news</a></p>@endif
    @endif
</div></section>
@endif
