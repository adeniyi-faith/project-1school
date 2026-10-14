<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    <link rel="canonical" href="{{ $pageUrl }}">
    <meta name="theme-color" content="{{ Str::before(Str::after($style, '--hdr-bg:'), ';') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $school->name }}">
    <meta property="og:title" content="{{ $title }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    <meta property="og:url" content="{{ $pageUrl }}">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    @if ($school->logo)<link rel="icon" href="{{ $school->logo_url }}">@endif
    <style>:root{ {!! $style !!} }</style>
    <style>@include('website.styles')</style>
    <noscript><style>.burger{display:none}.menu{display:block;position:static;background:none;color:inherit;padding:0}.menu a{font-size:1rem;display:inline-block;border:0;margin-right:1rem}</style></noscript>
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</head>
<body id="sheet" data-theme="{{ $theme }}" class="theme-{{ $theme }}">
<a class="skip" href="#main">Skip to content</a>

<header class="hdr">
    <div class="wrap hdr-in">
        <a class="brand" href="{{ $homeUrl }}">
            @if ($logo)<img src="{{ $logo }}" alt="" width="52" height="42">@endif
            <span>{{ $school->name }}</span>
        </a>
        <button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
        <nav class="menu" id="menu" aria-label="Main">
            @foreach ($menu as $item)
                <a href="{{ $item->is_home ? $homeUrl : $homeUrl.'/'.$item->slug }}" @if ($item->id === $page->id) aria-current="page" @endif>{{ $item->title }}</a>
            @endforeach
            <a class="btn btn--solid" href="{{ route('public.admission.show', $school) }}">Apply online</a>
        </nav>
    </div>
</header>

<main id="main">
    @if ($needsHiddenH1)<h1 class="sr">{{ $page->title }}</h1>@endif
    @forelse ($blocks as $block)
        @includeIf('website.blocks.'.$block['type'], ['b' => $block])
    @empty
        <section class="sec"><div class="wrap"><h1>{{ $page->title }}</h1><p>This page has no content yet.</p></div></section>
    @endforelse
</main>

<footer class="ftr">
    <div class="wrap">
        <div class="ftr-grid">
            <div>
                <div class="ftr-brand">@if ($logo)<img src="{{ $logo }}" alt="" width="52" height="42">@endif<span>{{ $school->name }}</span></div>
                @if ($site->tagline)<p style="margin:.75rem 0 0;opacity:.85">{{ $site->tagline }}</p>@endif
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
                    @foreach ($menu->take(5) as $item)<li><a href="{{ $item->is_home ? $homeUrl : $homeUrl.'/'.$item->slug }}">{{ $item->title }}</a></li>@endforeach
                    <li><a href="{{ route('public.admission.show', $school) }}">Apply online</a></li>
                    <li><a href="{{ route('login') }}">Sign in</a></li>
                </ul>
            </div>
        </div>
        <p class="legal">@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}</p>
    </div>
</footer>

<script>
(function(){
  var b=document.querySelector('.burger'),body=document.body;
  b.addEventListener('click',function(){var o=body.classList.toggle('menu-open');b.setAttribute('aria-expanded',o?'true':'false');});
  document.querySelectorAll('.menu a').forEach(function(a){a.addEventListener('click',function(){body.classList.remove('menu-open');});});
  @if ($preview)
  document.addEventListener('click',function(e){var a=e.target.closest('a');if(a){e.preventDefault();}});
  @endif
})();
</script>
</body>
</html>
