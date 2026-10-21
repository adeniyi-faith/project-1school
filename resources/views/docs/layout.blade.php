<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Guides') | Adino School Systems</title>
<meta name="description" content="@yield('description', 'Guides for schools and developers using Adino School Systems.')">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
<style>
:root{color-scheme:light;--paper:#FAF9F5;--card:#fff;--ink:#141413;--text:#2B2A27;--muted:#6B6A64;--line:#E7E4D9;--soft:#F0EEE6;--accent:#2A2F9E;--accent-soft:#ECEDFB;--dark:#141413;
--wm-font:"Figtree","Segoe UI",system-ui,sans-serif;--serif:"Fraunces",Georgia,"Times New Roman",serif;--sans:"Figtree","Segoe UI",system-ui,-apple-system,sans-serif;--mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,monospace}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font:17px/1.7 var(--sans);color:var(--text);background:var(--paper);-webkit-font-smoothing:antialiased;display:flex;min-height:100vh;flex-direction:column}
a{color:var(--accent)}
:focus-visible{outline:2px solid var(--accent);outline-offset:2px;border-radius:6px}
.wrap{width:100%;max-width:1180px;margin:0 auto;padding:0 24px}

/* top bar */
.top{position:sticky;top:0;z-index:30;background:rgba(250,249,245,.92);backdrop-filter:saturate(1.4) blur(10px);border-bottom:1px solid var(--line)}
.top .wrap{height:68px;display:flex;align-items:center;gap:20px}
.brand{color:var(--ink);font-size:17px;text-decoration:none;flex:none}
.brand .wm-t{opacity:.6}
.top-links{display:none;gap:26px;margin-left:12px;font-size:15px;font-weight:500}
.top-links a{color:var(--muted);text-decoration:none}
.top-links a:hover{color:var(--ink)}
.spacer{flex:1}
.find{position:relative;display:none;width:300px}
.find input{width:100%;height:40px;padding:0 14px 0 40px;border:1px solid var(--line);border-radius:999px;background:var(--card);font:500 15px var(--sans);color:var(--ink)}
.find svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted)}
.find input::placeholder{color:#9A988F}
.results{position:absolute;left:0;right:0;top:46px;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 18px 40px -14px rgba(20,20,19,.25);padding:6px;max-height:70vh;overflow:auto;z-index:40}
.results[hidden]{display:none}
.results a{display:block;padding:10px 12px;border-radius:10px;text-decoration:none;color:var(--ink);font-weight:600;font-size:15px;line-height:1.35}
.results a:hover,.results a:focus{background:var(--soft)}
.results a span{display:block;font-weight:400;color:var(--muted);font-size:13.5px}
.results p{margin:0;padding:12px;color:var(--muted);font-size:14.5px}
.icon-btn{width:42px;height:42px;display:grid;place-items:center;border:0;background:none;border-radius:12px;color:var(--ink);cursor:pointer}
.icon-btn:hover{background:var(--soft)}
.login{display:none;height:40px;align-items:center;padding:0 18px;border-radius:999px;background:var(--ink);color:#fff;text-decoration:none;font-weight:600;font-size:14.5px}
@media(min-width:60rem){.top-links,.find,.login{display:flex}.menu-btn{display:none}}

/* phone menu */
.drawer{position:fixed;inset:68px 0 0 0;z-index:25;background:var(--paper);overflow:auto;padding:8px 24px 40px;display:none}
.drawer.open{display:block}
.drawer .find{display:block;width:100%;margin:12px 0 8px}
.drawer h4,.side h4{margin:26px 0 8px;font:600 12px var(--sans);letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}
.drawer a.item{display:flex;justify-content:space-between;align-items:center;padding:14px 4px;border-bottom:1px solid var(--line);color:var(--ink);text-decoration:none;font-size:17px}
.drawer a.item[aria-current]{font-weight:600}

/* layout */
.shell{flex:1;display:grid;gap:40px;padding-top:36px;padding-bottom:72px}
@media(min-width:60rem){.shell{grid-template-columns:15.5rem minmax(0,1fr);gap:64px;padding-top:48px}}
.side{display:none;font-size:15px}
@media(min-width:60rem){.side{display:block;position:sticky;top:96px;align-self:start;max-height:calc(100vh - 120px);overflow:auto;padding-right:6px}}
.side h4:first-child{margin-top:0}
.side a{display:block;padding:7px 12px;margin:1px 0;border-radius:10px;color:var(--text);text-decoration:none;line-height:1.4}
.side a:hover{background:var(--soft)}
.side a[aria-current]{background:var(--card);color:var(--ink);font-weight:600;box-shadow:0 0 0 1px var(--line)}
main{min-width:0}

/* article */
.crumbs{display:flex;flex-wrap:wrap;gap:8px;align-items:center;font-size:14.5px;color:var(--muted);margin-bottom:22px}
.crumbs a{color:var(--muted);text-decoration:none}
.crumbs a:hover{color:var(--ink);text-decoration:underline}
.crumbs i{font-style:normal;opacity:.55}
h1.title{font:600 clamp(36px,6vw,56px)/1.06 var(--serif);letter-spacing:-.03em;font-variation-settings:"opsz" 120;color:var(--ink);margin:0 0 18px}
.lead{font-size:20px;line-height:1.55;color:var(--muted);margin:0 0 26px;max-width:40rem}
.meta{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);margin-bottom:34px;color:var(--muted);font-size:15px}
.copy{display:inline-flex;align-items:center;gap:8px;height:40px;padding:0 16px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font:600 14.5px var(--sans);cursor:pointer}
.copy:hover{background:var(--soft)}
.doc{max-width:44rem}
.doc>:first-child{margin-top:0}
.doc h2{font:600 clamp(26px,3.6vw,32px)/1.2 var(--serif);letter-spacing:-.02em;color:var(--ink);margin:2.4em 0 .5em;font-variation-settings:"opsz" 80}
.doc h3{font:600 20px/1.35 var(--sans);color:var(--ink);margin:1.9em 0 .4em}
.doc p{margin:0 0 1.1em}
.doc ul,.doc ol{padding-left:1.4em;margin:0 0 1.2em}
.doc li{margin:.35em 0}
.doc li::marker{color:#A9A79C}
.doc strong{color:var(--ink);font-weight:600}
.doc a{text-underline-offset:3px}
.doc code{font:500 .88em var(--mono);background:var(--soft);padding:.16em .4em;border-radius:6px;color:var(--ink)}
.doc pre{background:#1F1E1C;color:#EDEBE3;padding:18px 20px;border-radius:14px;overflow:auto;font-size:14px;line-height:1.65;margin:1.4em 0}
.doc pre code{background:none;padding:0;color:inherit;font-size:inherit}
.doc table{border-collapse:separate;border-spacing:0;width:100%;font-size:15.5px;display:block;overflow:auto;margin:1.4em 0;border:1px solid var(--line);border-radius:14px;background:var(--card)}
.doc th,.doc td{padding:11px 16px;text-align:left;vertical-align:top;border-bottom:1px solid var(--line)}
.doc tr:last-child td{border-bottom:0}
.doc th{background:var(--soft);font-weight:600;color:var(--ink);font-size:14px}
.doc blockquote{margin:1.5em 0;padding:16px 20px;border:1px solid var(--line);border-left:4px solid var(--accent);background:var(--card);border-radius:12px;color:var(--text)}
.doc blockquote p:last-child{margin:0}
.doc hr{border:0;border-top:1px solid var(--line);margin:2.4em 0}
.doc img{max-width:100%;border-radius:12px}

/* lists with document icons (related, popular, results) */
.rows{list-style:none;margin:0;padding:0;border-top:1px solid var(--line)}
.rows a{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 4px;border-bottom:1px solid var(--line);text-decoration:none;color:var(--ink)}
.rows a:hover{background:rgba(240,238,230,.55)}
.rows b{display:block;font:600 18px/1.35 var(--sans)}
.rows span.sum{display:block;color:var(--muted);font-size:15px;line-height:1.5;margin-top:2px}
.rows svg{flex:none;color:#55534C}
h2.section{font:600 clamp(28px,4vw,36px)/1.15 var(--serif);letter-spacing:-.02em;color:var(--ink);margin:64px 0 16px;font-variation-settings:"opsz" 80}
h2.section.small{font-size:26px;margin-top:56px}

/* home */
.hero{padding:12px 0 8px}
.hero h1{font:600 clamp(40px,7vw,68px)/1.04 var(--serif);letter-spacing:-.035em;color:var(--ink);margin:0 0 28px;max-width:14ch;font-variation-settings:"opsz" 144}
.hero .find{display:block;width:100%;max-width:640px}
.hero .find input{height:56px;font-size:17px;padding-left:50px;border-radius:18px}
.hero .find svg{left:18px}
.hero .results{top:62px}
.collections a{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:22px 4px;border-bottom:1px solid var(--line);text-decoration:none;color:var(--ink)}
.collections{border-top:1px solid var(--line)}
.collections a:hover{background:rgba(240,238,230,.55)}
.collections b{display:block;font:600 19px/1.3 var(--sans)}
.collections span{display:block;color:var(--muted);font-size:15.5px;margin-top:2px}
.collections svg{flex:none;color:#55534C}

.pager{display:grid;gap:14px;grid-template-columns:1fr;margin-top:56px;max-width:44rem}
@media(min-width:40rem){.pager{grid-template-columns:1fr 1fr}}
.pager a{display:block;padding:16px 18px;border:1px solid var(--line);border-radius:14px;background:var(--card);text-decoration:none;color:var(--ink);font-weight:600;line-height:1.35}
.pager a:hover{border-color:#CFCBBB}
.pager small{display:block;color:var(--muted);font-weight:500;font-size:13px;margin-bottom:3px}
.pager .next{text-align:right}

/* footer */
.foot{background:var(--dark);color:#CFCDC4;padding:56px 0 44px;margin-top:auto}
.foot .wrap{display:grid;gap:28px}
@media(min-width:48rem){.foot .wrap{grid-template-columns:1fr auto;align-items:end}}
.foot .brand{color:#fff;font-size:21px}
.foot p{margin:14px 0 0;max-width:30rem;font-size:15px;line-height:1.6;color:#A5A399}
.foot nav{display:flex;gap:22px;flex-wrap:wrap;font-size:15px}
.foot nav a{color:#E8E6DD;text-decoration:none}
.foot nav a:hover{text-decoration:underline}
.foot small{display:block;margin-top:6px;color:#8C8A80;font-size:13.5px}
@media print{.top,.side,.foot,.copy,.pager{display:none}.shell{display:block}body{background:#fff}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}}
@include('partials.wordmark-css')
</style>
</head>
<body>
@php
    $searchIndex = [];
    foreach ($groups as $g => $pages) { foreach ($pages as $p) { $searchIndex[] = ['t' => $p['title'], 's' => $p['summary'], 'g' => $g, 'u' => route('docs.show', $p['slug'])]; } }
@endphp
<header class="top">
    <div class="wrap">
        <a class="brand" href="{{ route('docs.index') }}" aria-label="Adino School Systems guides">@include('partials.wordmark')</a>
        <nav class="top-links" aria-label="Site"><a href="{{ route('docs.index') }}">All guides</a><a href="{{ url('/') }}">Home</a></nav>
        <span class="spacer"></span>
        <div class="find" data-find>{!! $searchIcon !!}<input type="search" placeholder="Search the guides" aria-label="Search the guides" autocomplete="off"><div class="results" hidden></div></div>
        <a class="login" href="{{ route('login') }}">Log in</a>
        <button class="icon-btn menu-btn" id="menuBtn" aria-label="Open menu" aria-expanded="false" aria-controls="drawer">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
    </div>
</header>

<div class="drawer" id="drawer">
    <div class="find" data-find>{!! $searchIcon !!}<input type="search" placeholder="Search the guides" aria-label="Search the guides" autocomplete="off"><div class="results" hidden></div></div>
    <a class="item" href="{{ route('docs.index') }}">All guides</a>
    <a class="item" href="{{ route('login') }}">Log in</a>
    @foreach ($groups as $group => $pages)
        <h4>{{ $group }}</h4>
        @foreach ($pages as $p)<a class="item" href="{{ route('docs.show', $p['slug']) }}" @if (($page['slug'] ?? null) === $p['slug']) aria-current="page" @endif>{{ $p['title'] }}</a>@endforeach
    @endforeach
</div>

<div class="wrap shell">
    <aside class="side" aria-label="Guides">
        @foreach ($groups as $group => $pages)
            <h4>{{ $group }}</h4>
            @foreach ($pages as $p)<a href="{{ route('docs.show', $p['slug']) }}" @if (($page['slug'] ?? null) === $p['slug']) aria-current="page" @endif>{{ $p['title'] }}</a>@endforeach
        @endforeach
    </aside>
    <main>@yield('content')</main>
</div>

<footer class="foot">
    <div class="wrap">
        <div>
            <a class="brand" href="{{ url('/') }}" aria-label="Adino School Systems home">@include('partials.wordmark')</a>
            <p>Fees, results, attendance, admissions and school websites in one place. These guides show you how to set it up and run it, one step at a time.</p>
        </div>
        <div>
            <nav aria-label="Footer"><a href="{{ route('docs.index') }}">All guides</a><a href="{{ url('/') }}">Home</a><a href="{{ route('login') }}">Log in</a></nav>
            <small>&copy; {{ date('Y') }} Adino Systems</small>
        </div>
    </div>
</footer>

<script>
(function(){
  var index = @json($searchIndex);
  function esc(s){return s.replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})}
  document.querySelectorAll('[data-find]').forEach(function(box){
    var input = box.querySelector('input'), out = box.querySelector('.results');
    function run(){
      var q = input.value.trim().toLowerCase();
      if(!q){ out.hidden = true; out.innerHTML = ''; return; }
      var words = q.split(/\s+/);
      var hits = index.filter(function(p){ var hay = (p.t+' '+p.s+' '+p.g).toLowerCase(); return words.every(function(w){return hay.indexOf(w) !== -1}); });
      out.innerHTML = hits.length
        ? hits.map(function(p){ return '<a href="'+p.u+'">'+esc(p.t)+'<span>'+esc(p.s)+'</span></a>'; }).join('')
        : '<p>No guide matches &ldquo;'+esc(input.value.trim())+'&rdquo;. Try a shorter word.</p>';
      out.hidden = false;
    }
    input.addEventListener('input', run);
    input.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){ input.value=''; run(); input.blur(); }
      if(e.key === 'Enter'){ var a = out.querySelector('a'); if(a){ location.href = a.href; } }
    });
    document.addEventListener('click', function(e){ if(!box.contains(e.target)) out.hidden = true; });
  });
  var btn = document.getElementById('menuBtn'), drawer = document.getElementById('drawer');
  btn.addEventListener('click', function(){
    var open = drawer.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    document.body.style.overflow = open ? 'hidden' : '';
  });
  var copy = document.getElementById('copyPage');
  if(copy){ copy.addEventListener('click', function(){
    var text = document.querySelector('.doc').innerText;
    (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function(){ copy.querySelector('span').textContent = 'Copied'; setTimeout(function(){ copy.querySelector('span').textContent = 'Copy page'; }, 1800); }).catch(function(){});
  }); }
})();
</script>
</body>
</html>
