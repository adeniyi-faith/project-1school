@php
    $links = collect($b['items'])->map(function ($item) use ($school, $homeUrl, $contactUrl) {
        $item['href'] = match ($item['link'] ?? 'none') { 'apply' => route('public.admission.show', $school), 'login' => $homeUrl.'/sign-in', 'contact' => $contactUrl, default => null };

        return $item;
    });
@endphp
<section class="actions"><div class="wrap">
    <div class="wa-acts">
        @foreach ($links as $item)
            @if ($item['href'])<a class="wa-act" href="{{ $item['href'] }}">@else<div class="wa-act">@endif
                <span class="wa-act-text"><b>{{ $item['title'] }}</b>@if ($item['body'] ?? '')<span>{{ $item['body'] }}</span>@endif</span>
                @if ($item['href'])<i class="wa-go" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 12h13m-5-6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></i>@endif
            @if ($item['href'])</a>@else</div>@endif
        @endforeach
    </div>
</div></section>
