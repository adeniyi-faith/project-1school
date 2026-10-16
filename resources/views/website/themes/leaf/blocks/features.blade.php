@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $tones = ['a', 'b', 'c'];
    $items = $b['items'] ?? [];
@endphp
<section class="sec lf-features {{ ($b['band'] ?? 'soft') === 'soft' ? 'sec--soft' : '' }}"><div class="wrap">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))
        <div class="sec-head center">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div>
    @endif
    <div class="lf-cards">
        @foreach ($items as $item)
            @php
                $pic = ($item['image'] ?? '') ? asset('storage/'.$item['image']) : null;
                $icon = $item['icon'] ?? 'none';
                $href = match ($item['link'] ?? 'none') { 'apply' => route('public.admission.show', $school), 'login' => $homeUrl.'/sign-in', 'contact' => $contactUrl, default => null };
                $label = ['apply' => 'Apply online', 'login' => 'Sign in', 'contact' => 'Talk to us'][$item['link'] ?? 'none'] ?? '';
            @endphp
            <article class="lf-card lf-card--{{ $tones[$loop->index % 3] }} {{ $pic ? 'lf-card--photo' : '' }}">
                @if ($pic)<div class="lf-card-pic"><img src="{{ $pic }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="800" height="560"></div>@endif
                <span class="lf-ico">@if ($icon !== 'none')@include('website.partials.icon', ['name' => $icon])@else<b>{{ $loop->iteration }}</b>@endif</span>
                <h3>{{ $item['title'] }}</h3>
                @if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                @if ($href)<a class="lf-more" href="{{ $href }}">{{ $label }}</a>@endif
            </article>
        @endforeach
    </div>
</div></section>
