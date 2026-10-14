@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}></div>@endif
    <div class="contact">
        @if ($place)<div class="card card--plain"><div class="card-body"><b>Address</b><a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($school->name.' '.$place) }}" target="_blank" rel="noopener">{{ $place }}</a></div></div>@endif
        @if ($school->phone)<div class="card card--plain"><div class="card-body"><b>Phone</b><a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></div></div>@endif
        @if ($school->email)<div class="card card--plain"><div class="card-body"><b>Email</b><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></div></div>@endif
        @if (! $place && ! $school->phone && ! $school->email)<p>Contact details have not been added yet.</p>@endif
    </div>
</div></section>
