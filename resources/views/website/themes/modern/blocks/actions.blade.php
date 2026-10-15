@php
    $links = collect($b['items'])->map(function ($item) use ($school, $homeUrl, $contactUrl) {
        $item['href'] = match ($item['link'] ?? 'none') { 'apply' => route('public.admission.show', $school), 'login' => $homeUrl.'/sign-in', 'contact' => $contactUrl, default => null };

        return $item;
    });
    $go = $links->firstWhere('link', 'apply') ?? $links->first(fn ($i) => $i['href']);
@endphp
<section class="actions"><div class="wrap">
    <div class="mo-panel">
        <h2>Get started</h2>
        <ul class="mo-rows">
            @foreach ($links as $item)
                <li>@if ($item['href'])<a href="{{ $item['href'] }}"><b>{{ $item['title'] }}</b>@if ($item['body'] ?? '')<span>{{ $item['body'] }}</span>@endif</a>@else<div><b>{{ $item['title'] }}</b>@if ($item['body'] ?? '')<span>{{ $item['body'] }}</span>@endif</div>@endif</li>
            @endforeach
        </ul>
        @if ($go && $go['href'])<a class="btn btn--navy" href="{{ $go['href'] }}">{{ ($go['link'] ?? '') === 'apply' ? 'Start your application' : $go['title'] }}</a>@endif
    </div>
</div></section>
