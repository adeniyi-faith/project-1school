@extends('website.layout')

@section('main')
<section class="sp-intro"><div class="wrap">
    <a class="sp-back" href="{{ \App\Website\SiteRenderer::galleryUrl($school) }}">&larr; All albums</a>
    <p class="sp-kicker">Photo album@if ($album->event_date) &middot; {{ $album->event_date->format('j F Y') }}@endif</p>
    <h1>{{ $album->title }}</h1>
    @if ($album->description)<p class="sp-lead">{{ $album->description }}</p>@endif
</div></section>
<section class="sp-body sp-body--wide"><div class="wrap">
    @include('website.partials.photo-grid', ['photos' => $photos])
</div></section>
@endsection
