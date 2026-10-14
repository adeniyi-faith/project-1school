@php $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null; @endphp
<footer class="ftr ftr--statement">
    <div class="wrap">
        <div class="ftr-main">
            <div class="ftr-box">
                <h2>Contact</h2>
                <p>
                    {{ $school->name }}@if ($place), {{ $place }}@endif.
                    @if ($school->email)<br>Email: <a href="mailto:{{ $school->email }}">{{ $school->email }}</a>.@endif
                    @if ($school->phone)<br>Phone: <a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a>.@endif
                </p>
                @if (count($social))
                    <ul class="social">
                        @foreach ($social as $key => $href)
                            <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="ftr-cta">
                <div class="btns">
                    <a class="btn btn--solid" href="{{ route('public.admission.show', $school) }}">Apply online</a>
                    @if ($phoneDigits)<a class="btn btn--ghost" href="tel:{{ $phoneDigits }}">Call {{ $school->phone }}</a>@else<a class="btn btn--ghost" href="{{ $homeUrl }}/sign-in">Sign in</a>@endif
                </div>
                <ul class="ftr-links">
                    @foreach ($menu->take(6) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="{{ $homeUrl }}/sign-in">Sign in</a></li>
                </ul>
            </div>
        </div>
        <div class="legal">
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}</p>
            <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a>@if ($site->tagline) <span aria-hidden="true">|</span> {{ $site->tagline }}@endif</p>
        </div>
    </div>
</footer>
