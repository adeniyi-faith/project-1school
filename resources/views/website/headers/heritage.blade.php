<div class="top">
    <div class="wrap top-in">
        <div class="top-info">
            @if ($school->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>@endif
            @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
        </div>
        <div class="top-links">
            @if ($site->option('apply_button'))<a href="{{ route('public.admission.show', $school) }}">Admissions</a>@endif
            <a href="{{ $homeUrl }}/sign-in">Parent, student and staff sign in</a>
        </div>
    </div>
</div>

<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand')
        <button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
        @include('website.partials.nav')
    </div>
</header>
