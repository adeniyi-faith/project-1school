<section class="block"><div class="wrap">
    @if ($b['heading'])<h2>{{ $b['heading'] }}</h2>@endif
    <div class="card">
        @php $place = collect([$school->address, $school->city, $school->state])->filter()->implode(', '); @endphp
        @if ($place)<p><strong>Address</strong><br>{{ $place }}</p>@endif
        @if ($school->phone)<p><strong>Phone</strong><br><a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></p>@endif
        @if ($school->email)<p><strong>Email</strong><br><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></p>@endif
        @if (! $place && ! $school->phone && ! $school->email)<p class="muted">Contact details have not been added yet.</p>@endif
    </div>
</div></section>
