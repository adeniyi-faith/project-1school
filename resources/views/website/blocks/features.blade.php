@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--soft"><div class="wrap">
    @if ($b['heading'] || $b['intro'])<div class="sec-head">@if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <div class="cards">
        @foreach ($b['items'] as $item)
            <article class="card {{ $item['image'] ? '' : 'card--plain' }}">
                @if ($item['image'])<img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="600" height="400">@endif
                <div class="card-body"><h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif</div>
            </article>
        @endforeach
    </div>
</div></section>
