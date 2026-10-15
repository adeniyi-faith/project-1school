<section class="sec sec--quote"><div class="wrap">
    <figure class="wa-quote">
        @if ($logo)<span class="wa-quote-logo"><img src="{{ $logo }}" alt="" width="64" height="64"></span>@else<span class="wa-quote-logo wa-quote-mark" aria-hidden="true">&ldquo;</span>@endif
        <blockquote><p>{{ $b['text'] }}</p></blockquote>
        @if (($b['who'] ?? '') !== '')<figcaption>{{ $b['who'] }}</figcaption>@endif
    </figure>
</div></section>
