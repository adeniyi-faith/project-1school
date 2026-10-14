@php
    $extra = ['news', 'gallery'];
    $primary = $menu->reject(fn ($m) => in_array($m['slug'], $extra))->values();
    $alt = $menu->filter(fn ($m) => in_array($m['slug'], $extra))->values();
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
@endphp
<footer class="ftr ftr--heritage">
    <div class="wrap">
        <a class="fh-brand" href="{{ $homeUrl }}">@if ($logo)<img src="{{ $logo }}" alt="" width="64" height="64">@endif<span>{{ $school->name }}</span></a>

        <div class="fh-visit">
            @if ($place)<p><a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($school->name.' '.$place) }}" target="_blank" rel="noopener">{{ $place }} (Map)</a></p>@endif
            @if ($school->phone)<p><a class="fh-strong" href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></p>@endif
            @if ($school->email)<p><a href="mailto:{{ $school->email }}">{{ $school->email }}</a></p>@endif
        </div>

        @if (count($social))
            <ul class="fh-social">
                @foreach ($social as $key => $href)
                    <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                @endforeach
            </ul>
        @endif

        <div class="fh-cols">
            @if ($primary->isNotEmpty())
                <nav aria-label="About"><h2>Our school</h2><ul>@foreach ($primary->take(7) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach</ul></nav>
            @endif
            <nav aria-label="Admissions and sign in"><h2>For families</h2><ul>
                @if ($apply)<li><a href="{{ $apply }}">Apply online</a></li>@endif
                <li><a href="{{ $homeUrl }}/sign-in">Parent sign in</a></li>
                <li><a href="{{ $homeUrl }}/sign-in">Student sign in</a></li>
                @if ($contactUrl)<li><a href="{{ $contactUrl }}">Contact us</a></li>@endif
            </ul></nav>
            @if ($alt->isNotEmpty())
                <nav class="fh-wide" aria-label="Quick links"><h2>Quick links</h2><ul>@foreach ($alt as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach</ul></nav>
            @endif
        </div>

        <ul class="fh-legal">
            <li><a href="{{ $homeUrl }}/sign-in">Staff sign in</a></li>
            <li><a href="{{ $homeUrl }}/sitemap.xml">Sitemap</a></li>
        </ul>
        <p class="fh-copy">@if ($site->footer_text){{ $site->footer_text }} &middot; @endif Copyright &copy; {{ now()->year }} {{ $school->name }}</p>
        <p class="fh-powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
    </div>
</footer>
