<a class="brand" href="{{ $homeUrl }}">
    @if ($logo)<img class="{{ $logoFit->classes($inMenu ?? false ? 'menu' : 'hdr') }}" src="{{ $logo }}" alt="{{ $school->name }} logo" width="52" height="42">@endif
    <span>{{ $school->name }}@if (($brandTagline ?? false) && $site->tagline)<small>{{ $site->tagline }}</small>@endif</span>
</a>
