@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))<div class="sec-head">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>@endif
    <div class="mo-acc">
        @foreach ($b['items'] as $item)
            @if ($item['body'])
                <details><summary>{{ $item['title'] }}</summary><p>{{ $item['body'] }}</p></details>
            @else
                <div class="mo-acc-row">{{ $item['title'] }}</div>
            @endif
        @endforeach
    </div>
</div></section>
