{{-- A section heading with an optional small label above it. Needs $b (the section), $tag (h1 or h2). The Leaf theme turns words between *stars* into its coloured word --}}
@if (($b['eyebrow'] ?? '') !== '')<p class="eyebrow">{{ $b['eyebrow'] }}</p>@endif
@if (($b['heading'] ?? '') !== '')<{{ $tag }}>@if (\App\Website\Themes::key($theme ?? null) === 'leaf'){!! \App\Website\Prose::accent($b['heading']) !!}@else{{ $b['heading'] }}@endif</{{ $tag }}>@endif
