@extends('website.layout')

@section('main')
<section class="sp-intro"><div class="wrap">
    <p class="sp-kicker">Gallery</p>
    <h1>Life at {{ $school->name }}</h1>
    <p class="sp-lead">Moments from our classrooms, sports field and events.</p>
</div></section>
<section class="sp-body sp-body--wide"><div class="wrap">
    @if ($albums->isEmpty())
        <p>No photos yet. Check back soon.</p>
    @else
        <div class="sp-cards">
            @foreach ($albums as $a)
                <a class="sp-card sp-card--album" href="{{ \App\Website\SiteRenderer::galleryUrl($school, $a) }}">
                    @if ($a->cover_path)<img src="{{ asset('storage/'.$a->cover_path) }}" alt="" loading="lazy" decoding="async" width="640" height="480">@endif
                    <span class="sp-card-body">
                        <span class="sp-kicker">{{ $a->photos_count }} {{ $a->photos_count === 1 ? 'photo' : 'photos' }}@if ($a->event_date) &middot; {{ $a->event_date->format('j M Y') }}@endif</span>
                        <b>{{ $a->title }}</b>
                    </span>
                </a>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
