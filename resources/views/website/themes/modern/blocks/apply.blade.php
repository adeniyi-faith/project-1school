@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="cta"><div class="wrap">
    @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
    @if ($b['body'])<p>{{ $b['body'] }}</p>@endif
    <div class="btns">
        @include('website.partials.button', ['kind' => $b['button'], 'variant' => 'solid'])
        @include('website.partials.button', ['kind' => $b['button2'], 'variant' => 'solid'])
    </div>
</div></section>
