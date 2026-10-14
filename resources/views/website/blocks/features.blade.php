@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--soft"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <div class="cards">
        @foreach ($b['items'] as $item)
            @php $icon = $item['icon'] ?? 'none'; @endphp
            <article class="card {{ ($item['image'] ?? '') || $icon !== 'none' ? '' : 'card--plain' }}">
                @if ($item['image'] ?? '')<img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="600" height="400">@endif
                <div class="card-body">
                    @if ($icon !== 'none')<span class="card-ico">@include('website.partials.icon', ['name' => $icon])</span>@endif
                    <h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                </div>
            </article>
        @endforeach
    </div>
</div></section>
