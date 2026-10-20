@extends('docs.layout')
@section('title', $page['title'])
@section('description', $page['summary'])
@section('content')
    <nav class="crumbs" aria-label="Breadcrumb">
        <a href="{{ route('docs.index') }}">Guides</a><i>&rsaquo;</i><span>{{ $page['group'] }}</span>
    </nav>
    <h1 class="title">{{ $page['title'] }}</h1>
    <p class="lead">{{ $page['summary'] }}</p>
    <div class="meta">
        <span>{{ $page['minutes'] ?? 1 }} min read</span>
        <button type="button" class="copy" id="copyPage">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg>
            <span>Copy page</span>
        </button>
    </div>
    <article class="doc">{!! $html !!}</article>

    @if (!empty($related))
        <h2 class="section small">Related articles</h2>
        <ul class="rows" style="max-width:44rem">
            @foreach (array_slice($related, 0, 4) as $r)
                <li><a href="{{ route('docs.show', $r['slug']) }}"><div><b>{{ $r['title'] }}</b><span class="sum">{{ $r['summary'] }}</span></div>{!! $docIcon !!}</a></li>
            @endforeach
        </ul>
    @endif

    <div class="pager">
        @if ($prev)<a href="{{ route('docs.show', $prev['slug']) }}"><small>Previous</small>{{ $prev['title'] }}</a>@else<span></span>@endif
        @if ($next)<a class="next" href="{{ route('docs.show', $next['slug']) }}"><small>Next</small>{{ $next['title'] }}</a>@endif
    </div>
@endsection
