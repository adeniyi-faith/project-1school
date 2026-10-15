<footer class="ftr">
    <div class="wrap">
        <div class="ftr-grid">
            <div>
                <div class="ftr-brand">@if ($logo)<img class="{{ $logoFit->classes('ftr') }}" src="{{ $logo }}" alt="{{ $school->name }} logo" width="52" height="42">@endif<span>{{ $school->name }}</span></div>
                @if ($site->tagline)<p class="ftr-tag">{{ $site->tagline }}</p>@endif
                @if (count($social))
                    <ul class="social">
                        @foreach ($social as $key => $href)
                            <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <h2>Visit us</h2>
                <ul>
                    @if ($place)<li><a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($school->name.' '.$place) }}" target="_blank" rel="noopener">{{ $place }} (Map)</a></li>@endif
                    @if ($school->phone)<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></li>@endif
                    @if ($school->email)<li><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></li>@endif
                </ul>
            </div>
            <div>
                <h2>Quick links</h2>
                <ul>
                    @foreach ($menu->take(6) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="{{ route('public.admission.show', $school) }}">Apply online</a></li>
                    <li><a href="{{ $homeUrl }}/sign-in">Sign in</a></li>
                </ul>
            </div>
        </div>
        <div class="legal">
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}@if ($privacyUrl) &middot; <a href="{{ $privacyUrl }}">Privacy Policy</a>@endif</p>
            <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
        </div>
    </div>
</footer>
