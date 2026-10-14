<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->is_home ? $school->name : $page->title.' · '.$school->name }}</title>
    @if ($site->meta_description)<meta name="description" content="{{ $site->meta_description }}">@endif
    <style>
        :root { --accent: {{ $css['accent'] }}; --bg: {{ $css['bg'] }}; --soft: {{ $css['soft'] }}; --text: {{ $css['text'] }}; --font: {!! $css['font'] !!}; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: var(--font); line-height: 1.6; }
        a { color: var(--accent); }
        .wrap { max-width: 64rem; margin: 0 auto; padding: 0 1.25rem; }
        header.top { border-bottom: 1px solid rgba(0,0,0,.08); background: var(--bg); position: sticky; top: 0; z-index: 5; }
        header.top .wrap { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem 1.5rem; padding-top: .75rem; padding-bottom: .75rem; }
        .brand { display: flex; align-items: center; gap: .6rem; font-weight: 700; font-size: 1.1rem; color: var(--text); text-decoration: none; }
        .brand img { height: 2.25rem; width: auto; }
        nav a { margin-right: 1rem; text-decoration: none; color: var(--text); font-size: .95rem; }
        nav a.on { color: var(--accent); font-weight: 600; }
        .btn { display: inline-block; background: var(--accent); color: #fff; padding: .7rem 1.4rem; border-radius: .5rem; text-decoration: none; font-weight: 600; }
        section.block { padding: 3rem 0; }
        section.block:nth-of-type(even) { background: var(--soft); }
        h1, h2 { line-height: 1.2; margin: 0 0 .75rem; }
        .hero { background: var(--accent); color: #fff; text-align: center; padding: 5rem 0; }
        .hero h1 { font-size: clamp(2rem, 6vw, 3.25rem); }
        .hero p { font-size: 1.2rem; opacity: .92; margin: 0 auto 1.75rem; max-width: 40rem; }
        .hero .btn { background: #fff; color: var(--accent); }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); }
        .card { background: var(--bg); border: 1px solid rgba(0,0,0,.08); border-radius: .75rem; padding: 1.25rem; }
        .card h3 { margin: 0 0 .35rem; }
        .muted { opacity: .65; font-size: .875rem; }
        footer { border-top: 1px solid rgba(0,0,0,.08); padding: 2rem 0; font-size: .9rem; }
    </style>
</head>
<body>
<header class="top">
    <div class="wrap">
        <a class="brand" href="{{ $homeUrl }}">
            @if ($school->logo)<img src="{{ $school->logo_url }}" alt="">@endif
            <span>{{ $school->name }}</span>
        </a>
        <nav>
            @foreach ($menu as $item)
                <a href="{{ $item->is_home ? $homeUrl : $homeUrl.'/'.$item->slug }}" class="{{ $item->id === $page->id ? 'on' : '' }}">{{ $item->title }}</a>
            @endforeach
        </nav>
    </div>
</header>

<main>
    @forelse ($page->blocks ?? [] as $block)
        @includeIf('website.blocks.'.$block['type'], ['b' => $block])
    @empty
        <section class="block"><div class="wrap"><h1>{{ $page->title }}</h1><p class="muted">This page has no content yet.</p></div></section>
    @endforelse
</main>

<footer>
    <div class="wrap">
        <strong>{{ $school->name }}</strong>
        @if ($site->tagline) &middot; {{ $site->tagline }}@endif
        @if ($site->footer_text)<div class="muted">{{ $site->footer_text }}</div>@endif
        <div class="muted"><a href="{{ route('login') }}">Staff, student and parent sign in</a></div>
    </div>
</footer>
</body>
</html>
