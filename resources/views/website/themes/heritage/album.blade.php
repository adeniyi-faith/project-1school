@extends('website.layout')

@section('main')
<section class="sec hp-head"><div class="wrap">
    <a class="crumb" href="{{ \App\Website\SiteRenderer::galleryUrl($school) }}">&larr; All albums</a>
    <h1>{{ $album->title }}</h1>
    @if ($album->event_date)<time class="date">{{ $album->event_date->format('F j, Y') }}</time>@endif
    @if ($album->description)<p class="hp-lead">{{ $album->description }}</p>@endif
</div></section>
<section class="sec sec--tight"><div class="wrap">
    @include('website.partials.photo-grid', ['photos' => $photos])
</div></section>
@endsection
