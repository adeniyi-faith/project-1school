@extends('docs.layout')
@section('title', $page['title'])
@section('description', $page['summary'])
@section('content')
    <p style="margin:0 0 6px;font-size:13px;color:var(--muted)">{{ $page['group'] }}</p>
    <h1>{{ $page['title'] }}</h1>
    <p class="lead">{{ $page['summary'] }}</p>
    <article class="doc">{!! $html !!}</article>
    <div class="pager">
        <span>@if ($prev)<a href="{{ route('docs.show', $prev['slug']) }}">&larr; {{ $prev['title'] }}</a>@endif</span>
        <span>@if ($next)<a href="{{ route('docs.show', $next['slug']) }}">{{ $next['title'] }} &rarr;</a>@endif</span>
    </div>
@endsection
