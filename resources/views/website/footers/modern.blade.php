@php
    $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null;
    $showApply = $site->option('apply_button');
@endphp
<footer class="ftr ftr--modern">
    <div class="mf-main">
        <div class="wrap">
            <div class="mf-brand">
                @if ($logo)<a href="{{ $homeUrl }}" class="mf-logo"><img src="{{ $logo }}" alt="" width="64" height="64"></a>@endif
                <a href="{{ $homeUrl }}" class="mf-name">{{ $school->name }}</a>
                @if ($site->tagline)<p class="mf-tag">{{ $site->tagline }}</p>@endif
            </div>
            <div class="mf-cols">
                @if ($place || $school->phone || $school->email)
                <section>
                    <h2>Contact</h2>
                    @if ($school->phone)<p>Phone<b><a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a></b></p>@endif
                    @if ($school->email)<p>Email<b><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></b></p>@endif
                    @if ($place)<p>Address<b>{{ $place }}</b></p>@endif
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
        </div>
    </div>
    <div class="mf-legal">
        <div class="wrap">
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}</p>
            <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
        </div>
    </div>
</footer>
