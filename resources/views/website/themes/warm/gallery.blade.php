@extends('website.layout')

@section('main')
@include('website.themes.warm.head', ['kicker' => 'Gallery', 'title' => 'Life at '.$school->name, 'lead' => 'Moments from our classrooms, sports field and events.'])
<section class="wa-body"><div class="wrap">
    @if ($albums->isEmpty())
        <p>No photos yet. Check back soon.</p>
    @else
        <div class="wa-albums">
            @foreach ($albums as $a)
                <a class="wa-album" href="{{ \App\Website\SiteRenderer::galleryUrl($school, $a) }}">
                    @if ($a->cover_path)<img src="{{ asset('storage/'.$a->cover_path) }}" alt="" loading="lazy" decoding="async" width="640" height="640">@else<span class="wa-album-blank"></span>@endif
                    <span class="wa-album-cap"><small>{{ $a->photos_count }} {{ $a->photos_count === 1 ? 'photo' : 'photos' }}@if ($a->event_date) &middot; {{ $a->event_date->format('j M Y') }}@endif</small><b>{{ $a->title }}</b></span>
                </a>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
