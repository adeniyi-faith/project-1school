@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $pic = $b['image'] !== ''; @endphp
<section class="sec"><div class="wrap">
    <div class="{{ $pic ? 'split split--'.$b['side'] : ($b['align'] === 'center' ? 'center prose' : 'prose') }}">
        <div class="{{ $pic ? 'prose' : '' }}">
            @if ($b['heading'])<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
            @foreach (preg_split('/\n\s*\n/', $b['body']) as $para)@if (trim($para) !== '')<p>{!! nl2br(e(trim($para))) !!}</p>@endif @endforeach
        </div>
        @if ($pic)<img src="{{ asset('storage/'.$b['image']) }}" alt="{{ $b['heading'] }}" loading="lazy" decoding="async" width="800" height="600">@endif
    </div>
</div></section>
