{{-- The navy title band every Modern inner page starts with. Needs $kicker, $title; optional $lead, $back (url), $backLabel, $meta --}}
<section class="mo-head"><div class="wrap">
    @if (! empty($back))<a class="mo-back" href="{{ $back }}">&larr; {{ $backLabel }}</a>@endif
    @if (! empty($kicker))<p class="mo-kick">{{ $kicker }}</p>@endif
    <h1>{{ $title }}</h1>
    @if (! empty($lead))<p class="mo-lead">{{ $lead }}</p>@endif
    @if (! empty($meta))<p class="mo-meta">{!! $meta !!}</p>@endif
</div><span class="mo-head-cut" aria-hidden="true"></span></section>
