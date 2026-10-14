<article class="news-item {{ $item['image'] ? 'news-item--pic' : '' }}">
    @if ($item['image'])<img src="{{ $item['image'] }}" alt="" loading="lazy" decoding="async" width="640" height="400">@endif
    <div class="news-body">
        <span class="tag">{{ $item['tag'] }}</span>
        <h3>@if ($item['url'])<a href="{{ $item['url'] }}">{{ $item['title'] }}</a>@else{{ $item['title'] }}@endif</h3>
        @if ($item['excerpt'])<p>{{ $item['excerpt'] }}</p>@endif
        @if ($item['date'])<time class="date" datetime="{{ $item['date']->toDateString() }}">{{ $item['date']->format('M j, Y') }}</time>@endif
    </div>
</article>
