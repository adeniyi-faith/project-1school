<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    <link rel="canonical" href="{{ $pageUrl }}">
    @if ($preview)<meta name="robots" content="noindex">@endif
    <meta name="theme-color" content="{{ \App\Website\Themes::vars($site->theme, $site->color, $site->color2)['hdr-bg'] }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $school->name }}">
    <meta property="og:title" content="{{ $title }}">
    @if ($description)<meta property="og:description" content="{{ $description }}">@endif
    <meta property="og:url" content="{{ $pageUrl }}">
    @if ($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    @if ($logo)<link rel="icon" href="{{ $logo }}">@endif
    @if ($newsUrl)<link rel="alternate" type="application/rss+xml" title="{{ $school->name }}" href="{{ $homeUrl }}/feed.xml">@endif
    <style>:root{ {!! $style !!} }</style>
    <style>@include('website.styles')@include('website.styles-themes')</style>
    <noscript><style>.burger{display:none}.menu{display:block;position:static;background:none;color:inherit;padding:0}.menu a{font-size:1rem;display:inline-block;border:0;margin-right:1rem}</style></noscript>
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</head>
<body id="sheet" data-theme="{{ $theme }}">
<a class="skip" href="#main">Skip to content</a>

@include('website.headers.'.\App\Website\Themes::key($theme))

<main id="main">
    @yield('main')
</main>

<footer class="ftr">
    <div class="wrap">
        <div class="ftr-grid">
            <div>
                <div class="ftr-brand">@if ($logo)<img src="{{ $logo }}" alt="" width="52" height="42">@endif<span>{{ $school->name }}</span></div>
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
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}</p>
            <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
        </div>
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
<div class="lb" id="lb" hidden role="dialog" aria-modal="true" aria-label="Photo viewer">
    <button class="lb-x" type="button" aria-label="Close">&times;</button>
    <button class="lb-nav lb-prev" type="button" aria-label="Previous photo">&lsaquo;</button>
    <figure><img alt=""><figcaption></figcaption></figure>
    <button class="lb-nav lb-next" type="button" aria-label="Next photo">&rsaquo;</button>
</div>
<script>
(function(){
  var links=[].slice.call(document.querySelectorAll('a[data-lightbox]'));
  if(!links.length)return;
  var lb=document.getElementById('lb'),img=lb.querySelector('img'),cap=lb.querySelector('figcaption'),i=0;
  function show(n){i=(n+links.length)%links.length;img.src=links[i].href;img.alt=links[i].querySelector('img').alt;cap.textContent=links[i].getAttribute('data-caption')||'';}
  function open(n){show(n);lb.hidden=false;document.body.style.overflow='hidden';lb.querySelector('.lb-x').focus();}
  function close(){lb.hidden=true;document.body.style.overflow='';img.src='';}
  links.forEach(function(a,n){a.addEventListener('click',function(e){e.preventDefault();open(n);});});
  lb.querySelector('.lb-x').addEventListener('click',close);
  lb.querySelector('.lb-prev').addEventListener('click',function(){show(i-1);});
  lb.querySelector('.lb-next').addEventListener('click',function(){show(i+1);});
  lb.addEventListener('click',function(e){if(e.target===lb)close();});
  document.addEventListener('keydown',function(e){if(lb.hidden)return;if(e.key==='Escape')close();if(e.key==='ArrowLeft')show(i-1);if(e.key==='ArrowRight')show(i+1);});
})();
</script>
</body>
</html>
