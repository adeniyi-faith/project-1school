@php
    $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null;
    $showApply = $site->option('apply_button');
@endphp
<footer class="ftr ftr--modern">
    <div class="mf-main">
        <div class="wrap">
            <div class="mf-brand">
                @if ($logo)<a href="{{ $homeUrl }}" class="mf-logo"><img class="{{ $logoFit->classes('ftr') }}" src="{{ $logo }}" alt="{{ $school->name }} logo" width="64" height="64" loading="lazy" decoding="async"></a>@endif
                <a href="{{ $homeUrl }}" class="mf-name">{{ $school->name }}</a>
                @if ($site->tagline)<p class="mf-tag">{{ $site->tagline }}</p>@endif
            </div>
            <div class="mf-cols">
                @if ($place || $school->phone || $school->email)
                <section>
                    <h2>Contact</h2>
                    @if ($school->phone)<p><span class="mf-lab"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>Phone</span><b><a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a></b></p>@endif
                    @if ($school->email)<p><span class="mf-lab"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>Email</span><b><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></b></p>@endif
                    @if ($place)<p><span class="mf-lab"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>Address</span><b>{{ $place }}</b></p>@endif
                </section>
                @endif
                <section>
                    <h2>Explore</h2>
                    <ul class="mf-links">
                        @foreach ($menu->take(7) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
                        <li><a href="{{ $homeUrl }}/sign-in">Sign in</a></li>
                    </ul>
                </section>
                @if ($showApply)
                <section>
                    <h2>Admission</h2>
                    <p>Ready to join us? Start your application online.</p>
                    <a class="btn btn--solid" href="{{ route('public.admission.show', $school) }}">Apply online</a>
                </section>
                @endif
            </div>
            @if (count($social))
                <ul class="social">
                    @foreach ($social as $key => $href)
                        <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                    @endforeach
                </ul>
            @endif
            <a class="mf-top" href="#sheet" aria-label="Back to top"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 15 7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
    </div>
    <div class="mf-legal">
        <div class="wrap">
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}@if ($privacyUrl) &middot; <a href="{{ $privacyUrl }}">Privacy Policy</a>@endif</p>
            <p class="powered">School Software by <a href="{{ url('/') }}" rel="noopener">Adino Systems</a></p>
        </div>
    </div>
</footer>
@if ($showApply && ! request()->routeIs('public.admission.*'))
    <a class="mo-sticky" href="{{ route('public.admission.show', $school) }}">Apply online</a>
@endif
