@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; $pic = $b['image'] !== ''; @endphp
<section class="sec"><div class="wrap">
    <div class="{{ $pic ? 'split split--'.$b['side'] : ($b['align'] === 'center' ? 'center prose' : 'prose') }}">
        <div class="{{ $pic ? 'prose' : '' }}">
            @include('website.partials.heading')
            {!! \App\Website\Prose::html($b['body'], 3) !!}
        </div>
        @if ($pic)<img src="{{ asset('storage/'.$b['image']) }}" alt="{{ $b['heading'] }}" loading="lazy" decoding="async" width="800" height="600">@endif
    </div>
</div></section>
