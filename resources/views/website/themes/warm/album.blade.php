@extends('website.layout')

@section('main')
@include('website.themes.warm.head', ['kicker' => 'Photo album'.($album->event_date ? ' | '.$album->event_date->format('j F Y') : ''), 'title' => $album->title, 'lead' => $album->description, 'back' => \App\Website\SiteRenderer::galleryUrl($school), 'backLabel' => 'All albums'])
<section class="wa-body"><div class="wrap">
    @include('website.partials.photo-grid', ['photos' => $photos])
</div></section>
@endsection
