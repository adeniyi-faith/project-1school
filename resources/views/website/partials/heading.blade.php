{{-- A section heading with an optional small label above it. Needs $b (the section), $tag (h1 or h2) --}}
@if (($b['eyebrow'] ?? '') !== '')<p class="eyebrow">{{ $b['eyebrow'] }}</p>@endif
@if (($b['heading'] ?? '') !== '')<{{ $tag }}>{{ $b['heading'] }}</{{ $tag }}>@endif
