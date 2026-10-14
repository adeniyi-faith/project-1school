@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--stats"><div class="wrap">
    @if ($b['heading'])<{{ $tag }} class="center">{{ $b['heading'] }}</{{ $tag }}>@endif
    <div class="stats">
        @foreach ($b['items'] as $item)
            @php $num = (string) $item['number']; $plus = str_ends_with($num, '+'); @endphp
            <div class="stat"><b>{{ $plus ? substr($num, 0, -1) : $num }}@if ($plus)<small>+</small>@endif</b><span>{{ $item['label'] }}</span></div>
        @endforeach
    </div>
</div></section>
