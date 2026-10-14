@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    @if ($news->isEmpty())
        <p>No news yet. Check back soon.</p>
    @else
        <div class="news">
            @foreach ($news->take($b['count']) as $item)
                <article class="news-item">
                    <span class="tag">News</span>
                    <h3>{{ $item->title }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $item->body)), 220) }}</p>
                    <time class="date" datetime="{{ $item->published_at->toDateString() }}">{{ $item->published_at->format('M j, Y') }}</time>
                </article>
            @endforeach
        </div>
    @endif
</div></section>
