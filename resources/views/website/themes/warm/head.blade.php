{{-- The maroon title band every Warm inner page starts with. Needs $kicker, $title; optional $lead, $back (url), $backLabel, $meta --}}
<section class="wa-head"><div class="wrap">
    @if (! empty($back))<a class="wa-back" href="{{ $back }}">&larr; {{ $backLabel }}</a>@endif
    @if (! empty($kicker))<p class="wa-kick"><span>{{ $kicker }}</span></p>@endif
    <h1>{{ $title }}</h1>
    @if (! empty($lead))<p class="wa-lead">{{ $lead }}</p>@endif
    @if (! empty($meta))<p class="wa-meta">{!! $meta !!}</p>@endif
</div></section>
