<section class="block"><div class="wrap">
    @if ($b['heading'])<h2>{{ $b['heading'] }}</h2>@endif
    @forelse ($news->take($b['count']) as $item)
        <div class="card" style="margin-bottom:.75rem">
            <h3>{{ $item->title }}</h3>
            <div class="muted">{{ $item->published_at->format('j F Y') }}</div>
            <div>{!! nl2br(e(\Illuminate\Support\Str::limit($item->body, 300))) !!}</div>
        </div>
    @empty
        <p class="muted">No news yet.</p>
    @endforelse
</div></section>
