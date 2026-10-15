@php $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null; @endphp
<footer class="ftr ftr--crest">
    <div class="wrap cr-f">
        <a href="{{ $homeUrl }}" class="cr-f-name">{{ $school->name }}</a>
        @if ($site->tagline ?? null)<p class="cr-f-tag">{{ $site->tagline }}</p>@endif
        <div class="cr-f-cols">
            <div class="cr-f-col">
                <h3>Contact</h3>
                <p>
                    @if ($school->phone)<a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a><br>@endif
                    @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
                </p>
                @if ($place)<p>{{ $place }}</p>@endif
            </div>
            <div class="cr-f-col">
                <h3>Explore</h3>
                <ul class="cr-f-links">
                    @foreach ($menu->take(7) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="{{ $homeUrl }}/sign-in">Sign in</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="cr-f-base"><div class="wrap">
        @if (count($social))
            <ul class="social">
                @foreach ($social as $key => $href)
                    <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                @endforeach
            </ul>
        @endif
        <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}@if ($privacyUrl) &middot; <a href="{{ $privacyUrl }}">Privacy Policy</a>@endif</p>
        <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
    </div></div>
</footer>
