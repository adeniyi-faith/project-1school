@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--band"><div class="wrap">
    @if ($b['heading'])<{{ $tag }} class="center">{{ $b['heading'] }}</{{ $tag }}>@endif
    <div class="wa-contact">
        @if ($place)<p>Address<b><a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($school->name.' '.$place) }}" target="_blank" rel="noopener">{{ $place }}</a></b></p>@endif
        @if ($school->phone)<p>Phone<b><a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></b></p>@endif
        @if ($school->email)<p>Email<b><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></b></p>@endif
        @if (! $place && ! $school->phone && ! $school->email)<p>Contact details have not been added yet.</p>@endif
    </div>
</div></section>
