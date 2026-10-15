@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--stats"><div class="wrap">
    @if ($b['heading'])<{{ $tag }} class="center">{{ $b['heading'] }}</{{ $tag }}>@endif
    <div class="mo-seals">
        @foreach ($b['items'] as $item)
            @php
                $label = mb_strtoupper(trim($item['label']));
                $times = mb_strlen($label) <= 10 ? 3 : (mb_strlen($label) <= 20 ? 2 : 1);
                $ring = implode(' · ', array_fill(0, $times, $label)).' · ';
                $id = 'seal'.$loop->index.'-'.substr(md5($label), 0, 4);
            @endphp
            <figure class="mo-seal">
                <svg viewBox="0 0 200 200" role="img" aria-label="{{ $item['number'] }} {{ $item['label'] }}">
                    <defs><path id="{{ $id }}" d="M100,100 m-74,0 a74,74 0 1,1 148,0 a74,74 0 1,1 -148,0"/></defs>
                    <circle cx="100" cy="100" r="96" fill="none" stroke="currentColor" stroke-width="2"/>
                    <circle cx="100" cy="100" r="58" fill="none" stroke="currentColor" stroke-width="1" stroke-dasharray="1 4"/>
                    <text class="mo-seal-ring"><textPath href="#{{ $id }}" textLength="460" lengthAdjust="spacing">{{ $ring }}</textPath></text>
                    <text class="mo-seal-num" x="100" y="112" text-anchor="middle" style="font-size:{{ mb_strlen((string) $item['number']) > 6 ? 20 : (mb_strlen((string) $item['number']) > 4 ? 26 : 34) }}px">{{ $item['number'] }}</text>
                </svg>
            </figure>
        @endforeach
    </div>
</div></section>
