@extends('website.layout')

@section('main')
<section class="sp-intro"><div class="wrap">
    <p class="sp-kicker">Find anything</p>
    <h1>Search</h1>
    <form class="sp-search" role="search" method="get" action="{{ $homeUrl }}/search">
        <label class="sr" for="q-page">Search this site</label>
        <input id="q-page" type="search" name="q" value="{{ $query }}" placeholder="Search pages, news and photos" maxlength="80" autocomplete="off">
        <button class="btn btn--solid" type="submit">Search</button>
    </form>
</div></section>
<section class="sp-body"><div class="wrap sp-narrow sp-results">
    @include('website.partials.search-results')
</div></section>
@endsection
