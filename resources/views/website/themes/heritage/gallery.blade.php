@extends('website.layout')

@section('main')
<section class="sec hp-head"><div class="wrap">
    <h1>Photo gallery</h1>
    <p class="hp-lead">Moments from our classrooms, sports field and events.</p>
</div></section>
<section class="sec sec--tight"><div class="wrap">
    @if ($albums->isEmpty())
        <p>No photos yet. Check back soon.</p>
    @else
        <div class="hp-albums">
            @foreach ($albums as $a)
                <a class="hp-album" href="{{ \App\Website\SiteRenderer::galleryUrl($school, $a) }}">
                    @if ($a->cover_path)<img src="{{ asset('storage/'.$a->cover_path) }}" alt="" loading="lazy" decoding="async">@endif
                    <span class="tag">{{ $a->photos_count }} {{ $a->photos_count === 1 ? 'photo' : 'photos' }}</span>
                    <b>{{ $a->title }}</b>
                    @if ($a->event_date)<time class="date">{{ $a->event_date->format('M j, Y') }}</time>@endif
                </a>
            @endforeach
        </div>
    @endif
</div></section>
@endsection
