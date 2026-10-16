@php
    $tag = ($b['_h1'] ?? false) ? 'h1' : 'h2';
    $stock = ['grove-writing', 'grove-classroom', 'grove-window', 'grove-friends', 'grove-boys'];
    $tones = ['olive', 'peach', 'slate'];
    $items = $b['items'] ?? [];
@endphp
<section class="gv-features">
    @if (($b['heading'] ?? '') || ($b['intro'] ?? '') || ($b['eyebrow'] ?? ''))
        <div class="wrap"><div class="sec-head center">@include('website.partials.heading') @if ($b['intro'])<p>{{ $b['intro'] }}</p>@endif</div></div>
    @endif
    @foreach ($items as $item)
        @php
            $pic = ($item['image'] ?? '') ? asset('storage/'.$item['image']) : asset('images/stock/'.$stock[$loop->index % count($stock)].'.jpg');
            $icon = $item['icon'] ?? 'none';
            $href = match ($item['link'] ?? 'none') { 'apply' => route('public.admission.show', $school), 'login' => $homeUrl.'/sign-in', 'contact' => $contactUrl, default => null };
            $label = ['apply' => 'Apply online', 'login' => 'Sign in', 'contact' => 'Talk to us'][$item['link'] ?? 'none'] ?? '';
        @endphp
        <article class="gv-band gv-band--{{ $tones[$loop->index % 3] }} {{ $loop->odd ? '' : 'gv-band--flip' }}">
            <div class="gv-band-pic"><img src="{{ $pic }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async" width="900" height="640"></div>
            <div class="gv-band-text"><div>
                <span class="gv-n">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                @if ($icon !== 'none')<span class="card-ico">@include('website.partials.icon', ['name' => $icon])</span>@endif
                <h3>{{ $item['title'] }}</h3>
                @if ($item['body'])<p>{{ $item['body'] }}</p>@endif
                @if ($href)<a class="btn btn--solid" href="{{ $href }}">{{ $label }}</a>@endif
            </div></div>
        </article>
    @endforeach
</section>
