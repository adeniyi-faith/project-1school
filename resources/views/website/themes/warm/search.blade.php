@extends('website.layout')

@section('main')
@include('website.themes.warm.head', ['kicker' => 'Find anything', 'title' => 'Search'])
<section class="wa-body"><div class="wrap wa-narrow">
    <form class="wa-search" role="search" method="get" action="{{ $homeUrl }}/search">
        <label class="sr" for="q-page">Search this site</label>
        <input id="q-page" type="search" name="q" value="{{ $query }}" placeholder="Search pages, news and photos" maxlength="80" autocomplete="off">
        <button class="btn btn--solid" type="submit">Search</button>
    </form>
    <div class="wa-results">@include('website.partials.search-results')</div>
</div></section>
@endsection
