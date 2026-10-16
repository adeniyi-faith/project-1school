<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    <link rel="canonical" href="{{ $pageUrl }}">
    @if ($preview || ($current ?? "") === "search")<meta name="robots" content="noindex">@endif
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
    @if ($fontsUrl = \App\Website\Themes::fontsUrl($theme))
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $fontsUrl }}">
    @endif
    <style>:root{ {!! $style !!} }</style>
    <style>@include('website.styles')@include('website.styles-themes')@include('website.styles-'.\App\Website\Themes::key($theme))@include('website.styles-cookie')@include('website.styles-live')</style>
    @php $customCss = trim(\App\Website\Themes::css($theme).' '.\App\Website\SafeCss::clean((string) $site->option('custom_css'))); @endphp
    @if ($customCss)<style>{!! $customCss !!}</style>@endif
    @include('website.partials.head-extra')
    <noscript><style>.burger{display:none}.menu{display:block;position:static;background:none;color:inherit;padding:0}.menu a{font-size:1rem;display:inline-block;border:0;margin-right:1rem}</style></noscript>
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
</head>
<body id="sheet" data-theme="{{ \App\Website\Themes::key($theme) }}" data-site-theme="{{ $theme }}" data-page="{{ $current ?? '' }}">
<a class="skip" href="#main">Skip to content</a>

@include('website.partials.announcement')
@include('website.headers.'.\App\Website\Themes::key($theme))

<main id="main">
    @yield('main')
</main>

@include('website.partials.footer-signup')
@include('website.footers.'.(view()->exists('website.footers.'.\App\Website\Themes::key($theme)) ? \App\Website\Themes::key($theme) : 'default'))

<script>
(function(){
  var b=document.querySelector('.burger'),body=document.body;
  // keep the top menu on one line: when the school name had to be cut short, tighten the menu spacing
  var fit=function(){body.classList.remove('nav-tight');var n=document.querySelector('.hdr .brand span');
    if(n&&window.matchMedia('(min-width:72rem)').matches&&(n.scrollWidth>n.clientWidth+1||n.scrollHeight>n.clientHeight+1)){body.classList.add('nav-tight');}};
  fit();window.addEventListener('resize',function(){window.requestAnimationFrame(fit);});window.addEventListener('load',fit);
  // the phone menu starts exactly where the header ends, even with an announcement bar above it
  var place=function(){var h=document.querySelector('.hdr');if(h){body.style.setProperty('--menu-top',Math.max(0,Math.round(h.getBoundingClientRect().bottom))+'px');}};
  b.addEventListener('click',function(){place();var o=body.classList.toggle('menu-open');b.setAttribute('aria-expanded',o?'true':'false');});
  window.addEventListener('resize',function(){if(body.classList.contains('menu-open')){place();}});
  var st=document.querySelector('[data-search-toggle]'),sb=document.getElementById('sbar');
  if(st&&sb){st.addEventListener('click',function(e){e.preventDefault();var o=sb.hidden;sb.hidden=!o;body.classList.toggle('search-open',o);st.setAttribute('aria-expanded',o?'true':'false');if(o){body.classList.remove('menu-open');sb.querySelector('input').focus();}});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!sb.hidden){sb.hidden=true;body.classList.remove('search-open');st.setAttribute('aria-expanded','false');st.focus();}});}
  document.querySelectorAll('[data-menu-close]').forEach(function(x){x.addEventListener('click',function(){body.classList.remove('menu-open');b.setAttribute('aria-expanded','false');});});
  document.querySelectorAll('[data-slider]').forEach(function(sl){
    var tr=sl.querySelector('.tsl-track'),nav=sl.querySelector('.tsl-nav');if(!tr||!nav)return;
    var items=[].slice.call(tr.children),dots=[].slice.call(nav.querySelectorAll('.tsl-dots i'));nav.hidden=false;
    var cur=function(){var w=items[0].offsetWidth+parseFloat(getComputedStyle(tr).columnGap||0);return Math.max(0,Math.min(items.length-1,Math.round(tr.scrollLeft/w)));};
    var mark=function(){var c=cur();dots.forEach(function(d,k){d.classList.toggle('on',k===c);});};
    var go=function(n){n=Math.max(0,Math.min(items.length-1,n));tr.scrollTo({left:items[n].offsetLeft-tr.offsetLeft,behavior:'smooth'});};
    nav.querySelectorAll('.tsl-btn').forEach(function(x){x.addEventListener('click',function(){go(cur()+parseInt(x.getAttribute('data-dir'),10));});});
    tr.addEventListener('scroll',function(){window.requestAnimationFrame(mark);},{passive:true});mark();
  });
  var still=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('[data-rotate]').forEach(function(el){
    var list;try{list=JSON.parse(el.getAttribute('data-rotate'));}catch(e){return;}
    if(!list||list.length<2||still)return;var i=0;
    setInterval(function(){el.classList.add('rot-out');setTimeout(function(){i=(i+1)%list.length;el.textContent=list[i];el.classList.remove('rot-out');},350);},3200);
  });
  var sc=function(){body.classList.toggle('scrolled',window.scrollY>8);};sc();window.addEventListener('scroll',sc,{passive:true});
  document.querySelectorAll('.menu a').forEach(function(a){a.addEventListener('click',function(){body.classList.remove('menu-open');});});
  @if ($preview)
  document.addEventListener('click',function(e){var a=e.target.closest('a');if(a){e.preventDefault();}});
  @endif
})();
</script>
@include('website.partials.whatsapp')
@include('website.partials.cookie-notice')
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
