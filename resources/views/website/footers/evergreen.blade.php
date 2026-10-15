@php $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null; $chev = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'; @endphp
<footer class="ftr ftr--evergreen">
    <div class="wrap eg-f">
        <a href="{{ $homeUrl }}" class="eg-f-name">{{ $school->name }}</a>
        @if ($site->tagline ?? null)<p class="eg-f-tag">{{ $site->tagline }}</p>@endif
        <address class="eg-f-addr">
            @if ($place){{ $place }}<br>@endif
            @if ($school->phone)<a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a>@endif
            @if ($school->email)<br><a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
        </address>
        <ul class="eg-f-links">
            @foreach ($menu->take(7) as $item)<li><a href="{{ $item['url'] }}"><span>{{ $item['label'] }}</span>{!! $chev !!}</a></li>@endforeach
            <li><a href="{{ $homeUrl }}/sign-in"><span>Sign in</span>{!! $chev !!}</a></li>
        </ul>
        @if (count($social))
            <ul class="social">
                @foreach ($social as $key => $href)
                    <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                @endforeach
            </ul>
        @endif
    </div>
    <div class="eg-f-legal"><div class="wrap">
        <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}@if ($privacyUrl) &middot; <a href="{{ $privacyUrl }}">Privacy Policy</a>@endif</p>
        <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
    </div></div>
</footer>
