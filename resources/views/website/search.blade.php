@extends('website.layout')

@section('main')
<section class="sec page-head"><div class="wrap">
    <h1>Search</h1>
    <form class="sbar-in sbar-page" role="search" method="get" action="{{ $homeUrl }}/search">
        <label class="sr" for="q-page">Search this site</label>
        <input id="q-page" type="search" name="q" value="{{ $query }}" placeholder="Search pages, news and photos" maxlength="80" autocomplete="off" autofocus>
        <button type="submit">Search</button>
    </form>
</div></section>
<section class="sec sec--tight"><div class="wrap narrow-wide">
    @include('website.partials.search-results')
</div></section>
@endsection
