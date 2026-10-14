@extends('website.layout')

@section('main')
<section class="sec page-head"><div class="wrap">
    <a class="crumb" href="{{ \App\Website\SiteRenderer::galleryUrl($school) }}">&larr; All albums</a>
    <h1>{{ $album->title }}</h1>
    @if ($album->event_date)<p class="date">{{ $album->event_date->format('F j, Y') }}</p>@endif
    @if ($album->description)<p class="lede">{{ $album->description }}</p>@endif
</div></section>
<section class="sec sec--tight"><div class="wrap">
    @include('website.partials.photo-grid', ['photos' => $photos])
</div></section>
@endsection
