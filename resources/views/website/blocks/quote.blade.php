<section class="sec sec--quote"><div class="wrap">
    <figure class="quote">
        <blockquote><p>{{ $b['text'] }}</p></blockquote>
        @if (($b['who'] ?? '') !== '')<figcaption>{{ $b['who'] }}</figcaption>@endif
    </figure>
</div></section>
