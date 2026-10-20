@php $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null; @endphp
<footer class="ftr ftr--warm">
    @if ($logo)<img class="wf-mark" src="{{ $logo }}" alt="" aria-hidden="true">@endif
    <div class="wrap wf-in">
        <a href="{{ $homeUrl }}" class="wf-name">{{ $school->name }}</a>
        <address class="wf-addr">
            @if ($place){{ $place }}<br>@endif
            @if ($school->phone)<a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a>@endif
            @if ($school->phone && $school->email) &middot; @endif
            @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
        </address>
        <ul class="wf-links">
            @foreach ($menu->take(6) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
            <li><a href="{{ $homeUrl }}/sign-in">Sign in</a></li>
        </ul>
        <div class="wf-icons">
            @if (count($social))
                <ul class="social">
                    @foreach ($social as $key => $href)
                        <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                    @endforeach
                </ul>
            @endif
            <a class="wf-up" href="#sheet" aria-label="Back to top"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V6m-6 6 6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <div class="wf-legal">
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}@if ($privacyUrl) &middot; <a href="{{ $privacyUrl }}">Privacy Policy</a>@endif</p>
            <p class="powered">School Software by <a href="{{ url('/') }}" rel="noopener">Adino Systems</a></p>
        </div>
    </div>
</footer>
