@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $band = $b['band'] ?? 'plain'; @endphp
<section class="sec {{ $band === 'soft' ? 'sec--soft' : ($band === 'dark' ? 'sec--dark' : '') }}"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <ol class="steps">
        @foreach ($b['items'] as $item)
            <li><span class="step-n">{{ $loop->iteration }}</span><div class="step-t"><h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif</div></li>
        @endforeach
    </ol>
</div></section>
