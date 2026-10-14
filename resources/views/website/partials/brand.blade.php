<a class="brand" href="{{ $homeUrl }}">
    @if ($logo)<img src="{{ $logo }}" alt="" width="52" height="42">@endif
    <span>{{ $school->name }}@if (($brandTagline ?? false) && $site->tagline)<small>{{ $site->tagline }}</small>@endif</span>
</a>
