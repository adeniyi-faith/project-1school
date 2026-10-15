@extends('website.layout')

@section('main')
@include('website.themes.modern.head', ['kicker' => 'Gallery', 'title' => 'Life at '.$school->name, 'lead' => 'Moments from our classrooms, sports field and events.'])
<section class="mo-body"><div class="wrap">
    @if ($albums->isEmpty())
        <p>No photos yet. Check back soon.</p>
    @else
        <div class="mo-albums">
            @foreach ($albums as $a)
                <a class="mo-album" href="{{ \App\Website\SiteRenderer::galleryUrl($school, $a) }}">
                    @if ($a->cover_path)<img src="{{ asset('storage/'.$a->cover_path) }}" alt="" loading="lazy" decoding="async" width="640" height="640">@else<span class="mo-album-blank"></span>@endif
                    <span class="mo-album-cap"><small>{{ $a->photos_count }} {{ $a->photos_count === 1 ? 'photo' : 'photos' }}@if ($a->event_date) &middot; {{ $a->event_date->format('j M Y') }}@endif</small><b>{{ $a->title }}</b></span>
                </a>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
