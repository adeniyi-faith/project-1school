@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--band"><div class="wrap">
    @if ($b['heading'])<{{ $tag }} class="center">{{ $b['heading'] }}</{{ $tag }}>@endif
    <div class="wa-stats">
        @foreach ($b['items'] as $item)
            <div class="wa-stat"><b>{{ $item['number'] }}</b><span>{{ $item['label'] }}</span></div>
        @endforeach
    </div>
</div></section>
