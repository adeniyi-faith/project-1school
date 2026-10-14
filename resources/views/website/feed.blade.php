<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ $school->name }}</title>
    <link>{{ \App\Website\SiteRenderer::homeUrl($school) }}</link>
    <description>{{ $site->meta_description ?: 'News and events from '.$school->name }}</description>
    <atom:link href="{{ \App\Website\SiteRenderer::homeUrl($school) }}/feed.xml" rel="self" type="application/rss+xml"/>
@foreach ($posts as $p)
    <item>
        <title>{{ $p->title }}</title>
        <link>{{ \App\Website\SiteRenderer::postUrl($school, $p) }}</link>
        <guid isPermaLink="true">{{ \App\Website\SiteRenderer::postUrl($school, $p) }}</guid>
        <pubDate>{{ $p->published_at->toRfc2822String() }}</pubDate>
        <category>{{ $p->categoryLabel() }}</category>
        <description>{{ $summary($p) }}</description>
    </item>
@endforeach
</channel>
</rss>
