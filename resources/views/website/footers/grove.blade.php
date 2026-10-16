@php $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null; $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null; @endphp
<footer class="ftr ftr--grove">
    <div class="wrap">
        <div class="gv-f-cta">
            <div>
                <p class="gv-f-kick">Come and see us</p>
                <h2>Meet the people who will know your child by name</h2>
            </div>
            <div class="btns">
                @if ($apply)<a class="btn btn--solid" href="{{ $apply }}">Apply online</a>@endif
                @if ($phoneDigits)<a class="btn btn--ghost" href="tel:{{ $phoneDigits }}">Call {{ $school->phone }}</a>@else<a class="btn btn--ghost" href="{{ $contactUrl ?? $homeUrl }}">Contact us</a>@endif
            </div>
        </div>
        <div class="gv-f-grid">
            <div class="gv-f-id">
                <a href="{{ $homeUrl }}" class="gv-f-name">{{ $school->name }}</a>
                @if ($site->tagline)<p class="gv-f-line">{{ $site->tagline }}</p>@endif
                @if (count($social))
                    <ul class="social">
                        @foreach ($social as $key => $href)
                            <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <h3>Visit</h3>
                <address>@if ($place){{ $place }}@else Ask the school office for directions.@endif</address>
            </div>
            <div>
                <h3>Talk to us</h3>
                <address>
                    @if ($school->phone)<a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a><br>@endif
                    @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
                </address>
            </div>
            <div>
                <h3>Explore</h3>
                <ul class="gv-f-links">
                    @foreach ($menu->take(6) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
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
