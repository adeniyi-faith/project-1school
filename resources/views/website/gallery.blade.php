@extends('website.layout')

@section('main')
<section class="sec page-head"><div class="wrap">
    <p class="eyebrow">Gallery</p>
    <h1>Life at {{ $school->name }}</h1>
    <p class="lede">Moments from our classrooms, sports field and events.</p>
</div></section>
<section class="sec sec--tight"><div class="wrap">
    @if ($albums->isEmpty())
        <p>No photos yet. Check back soon.</p>
    @else
        <div class="albums">
            @foreach ($albums as $a)
                <a class="album" href="{{ \App\Website\SiteRenderer::galleryUrl($school, $a) }}">
                    @if ($a->cover_path)<img src="{{ asset('storage/'.$a->cover_path) }}" alt="" loading="lazy" decoding="async">@endif
                    <span class="album-body">
                        <b>{{ $a->title }}</b>
                        <span>{{ $a->photos_count }} {{ $a->photos_count === 1 ? 'photo' : 'photos' }}@if ($a->event_date) &middot; {{ $a->event_date->format('M j, Y') }}@endif</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
