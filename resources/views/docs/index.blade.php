@extends('docs.layout')
@section('title', 'Guides')
@section('description', 'Step-by-step guides for running your school on Adino School Systems, and for developers building on it.')
@section('content')
    <section class="hero">
        <h1>How can we help?</h1>
        <div class="find" data-find>{!! $searchIcon !!}<input type="search" placeholder="Search the guides" aria-label="Search the guides" autocomplete="off"><div class="results" hidden></div></div>
    </section>

    <h2 class="section">Browse by topic</h2>
    <div class="collections">
        @foreach ($groups as $group => $pages)
            @php $first = $pages[0] ?? null; @endphp
            @if ($first)
            <a href="{{ route('docs.show', $first['slug']) }}">
                <div><b>{{ $group }}</b><span>{{ count($pages) }} {{ \Illuminate\Support\Str::plural('article', count($pages)) }} &middot; {{ $first['summary'] }}</span></div>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
            @endif
        @endforeach
    </div>

    @foreach ($groups as $group => $pages)
        <h2 class="section small">{{ $group }}</h2>
        <ul class="rows">
            @foreach ($pages as $p)
                <li><a href="{{ route('docs.show', $p['slug']) }}"><div><b>{{ $p['title'] }}</b><span class="sum">{{ $p['summary'] }}</span></div>{!! $docIcon !!}</a></li>
            @endforeach
        </ul>
    @endforeach
@endsection
