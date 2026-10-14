{{-- A wall of photos that open large in a lightbox. Needs $photos: a list of [path, alt, caption] --}}
<div class="photos">
    @foreach ($photos as $ph)
        <a class="photo" href="{{ asset('storage/'.$ph['path']) }}" data-lightbox data-caption="{{ $ph['caption'] ?? '' }}">
            <img src="{{ asset('storage/'.$ph['path']) }}" alt="{{ $ph['alt'] }}" loading="lazy" decoding="async">
            @if ($ph['caption'] ?? '')<span class="photo-cap">{{ $ph['caption'] }}</span>@endif
        </a>
    @endforeach
</div>
