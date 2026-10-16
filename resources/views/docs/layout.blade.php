<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Guides') | SchoolRuns</title>
<meta name="description" content="@yield('description', 'Guides for schools and developers using SchoolRuns.')">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Figtree:wght@400;500;600&display=swap">
<style>
:root{color-scheme:light;--ink:#0C1738;--cobalt:#3346E0;--line:#E4E8F2;--mist:#F5F7FC;--text:#0E1A3A;--muted:#5A6482;--display:"Plus Jakarta Sans","Helvetica Neue",Arial,sans-serif;--body:"Figtree","Segoe UI",system-ui,sans-serif}
*{box-sizing:border-box}
body{margin:0;font:16px/1.65 var(--body);color:var(--text);background:#fff;-webkit-font-smoothing:antialiased}
a{color:var(--cobalt)}
.bar{background:var(--ink);color:#fff}
.bar-in{max-width:1160px;margin:0 auto;padding:0 20px;height:60px;display:flex;align-items:center;justify-content:space-between;gap:16px}
.bar a{color:#fff;text-decoration:none;font:700 19px var(--display)}
.bar nav{display:flex;gap:20px;font-size:15px}
.bar nav a{font:500 15px var(--body);opacity:.85}
.shell{max-width:1160px;margin:0 auto;padding:0 20px;display:grid;gap:32px}
@media(min-width:56rem){.shell{grid-template-columns:16rem minmax(0,1fr);padding-top:32px}}
.side{padding-top:20px;font-size:15px}
@media(min-width:56rem){.side{position:sticky;top:20px;align-self:start;max-height:calc(100vh - 40px);overflow:auto}}
.side h4{margin:18px 0 6px;font:700 12px var(--display);letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.side h4:first-child{margin-top:0}
.side a{display:block;padding:6px 10px;border-radius:8px;color:var(--text);text-decoration:none}
.side a:hover{background:var(--mist)}
.side a[aria-current]{background:var(--mist);color:var(--cobalt);font-weight:600}
main{padding:20px 0 64px;min-width:0}
main h1{font:700 clamp(28px,5vw,40px)/1.15 var(--display);margin:0 0 8px;letter-spacing:-.02em}
.lead{color:var(--muted);font-size:18px;margin:0 0 24px}
.doc h2{font:700 24px/1.25 var(--display);margin:36px 0 8px;letter-spacing:-.01em}
.doc h3{font:700 18px var(--display);margin:24px 0 4px}
.doc p,.doc li{max-width:46rem}
.doc code{background:var(--mist);padding:2px 6px;border-radius:6px;font-size:.9em}
.doc pre{background:var(--ink);color:#e8ecff;padding:16px;border-radius:12px;overflow:auto;font-size:13.5px;line-height:1.55}
.doc pre code{background:none;padding:0;color:inherit}
.doc table{border-collapse:collapse;width:100%;font-size:15px;display:block;overflow:auto}
.doc th,.doc td{border:1px solid var(--line);padding:8px 12px;text-align:left;vertical-align:top}
.doc th{background:var(--mist)}
.doc blockquote{margin:16px 0;padding:8px 16px;border-left:4px solid var(--cobalt);background:var(--mist);border-radius:0 8px 8px 0}
.cards{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(16rem,1fr))}
.cards a{display:block;padding:18px;border:1px solid var(--line);border-radius:14px;text-decoration:none;color:var(--text)}
.cards a:hover{border-color:var(--cobalt)}
.cards b{display:block;font:700 17px var(--display);margin-bottom:4px}
.cards span{color:var(--muted);font-size:15px}
.pager{display:flex;justify-content:space-between;gap:16px;margin-top:48px;padding-top:20px;border-top:1px solid var(--line);font-size:15px}
</style>
</head>
<body>
<header class="bar"><div class="bar-in"><a href="{{ url('/') }}">SchoolRuns <span style="opacity:.6;font-weight:500">Guides</span></a><nav><a href="{{ route('docs.index') }}">All guides</a><a href="{{ route('login') }}">Log in</a></nav></div></header>
<div class="shell">
    <aside class="side" aria-label="Guides">
        @foreach ($groups as $group => $pages)
            <h4>{{ $group }}</h4>
            @foreach ($pages as $p)<a href="{{ route('docs.show', $p['slug']) }}" @if (($page['slug'] ?? null) === $p['slug']) aria-current="page" @endif>{{ $p['title'] }}</a>@endforeach
        @endforeach
    </aside>
    <main>@yield('content')</main>
</div>
</body>
</html>
