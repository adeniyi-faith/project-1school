@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <ol class="wa-steps">
        @foreach ($b['items'] as $item)
            <li><span class="wa-step-n">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><h3>{{ $item['title'] }}</h3>@if ($item['body'])<p>{{ $item['body'] }}</p>@endif</div></li>
        @endforeach
    </ol>
</div></section>
