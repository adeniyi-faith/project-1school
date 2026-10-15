@extends('docs.layout')
@section('title', 'Guides')
@section('content')
    <h1>Guides</h1>
    <p class="lead">How to run your school on SchoolRuns, and how developers can build on top of it.</p>
    @foreach ($groups as $group => $pages)
        <h2 style="font:700 22px var(--display);margin:32px 0 12px">{{ $group }}</h2>
        <div class="cards">@foreach ($pages as $p)<a href="{{ route('docs.show', $p['slug']) }}"><b>{{ $p['title'] }}</b><span>{{ $p['summary'] }}</span></a>@endforeach</div>
    @endforeach
@endsection
