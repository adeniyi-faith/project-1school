@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--soft"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    <div class="faq">
        @foreach ($b['items'] as $item)
            <details><summary>{{ $item['question'] }}</summary><p>{!! nl2br(e($item['answer'])) !!}</p></details>
        @endforeach
    </div>
</div></section>
