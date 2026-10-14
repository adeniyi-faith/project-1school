@php $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2'; @endphp
<section class="sec sec--tint"><div class="wrap">
    <article class="profile">
        @if ($b['image'] ?? '')<img src="{{ asset('storage/'.$b['image']) }}" alt="{{ $b['name'] }}" loading="lazy" decoding="async" width="800" height="900">@endif
        <div class="profile-body">
            @if (($b['eyebrow'] ?? '') !== '')<p class="eyebrow">{{ $b['eyebrow'] }}</p>@endif
            <{{ $tag }}>{{ $b['name'] }}</{{ $tag }}>
            @if (($b['role'] ?? '') !== '')<p class="profile-role">{{ $b['role'] }}</p>@endif
            <div class="prose">{!! \App\Website\Prose::html($b['body'] ?? '', 3) !!}</div>
        </div>
    </article>
</div></section>
