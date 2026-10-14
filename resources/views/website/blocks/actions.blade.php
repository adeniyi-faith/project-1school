<section class="actions"><div class="wrap">
    <div class="actions-grid">
        @foreach ($b['items'] as $item)
            @php
                $href = match ($item['link'] ?? 'none') { 'apply' => route('public.admission.show', $school), 'login' => $homeUrl.'/sign-in', 'contact' => $contactUrl, default => null };
                $tagName = $href ? 'a' : 'div';
            @endphp
            <{{ $tagName }} class="action" @if ($href) href="{{ $href }}" @endif>
                <span class="action-ico">@include('website.partials.icon', ['name' => $item['icon'] ?? 'none'])</span>
                <span class="action-text"><b>{{ $item['title'] }}</b>@if ($item['body'] ?? '')<span>{{ $item['body'] }}</span>@endif</span>
                @if ($href)<span class="action-go">@include('website.partials.icon', ['name' => 'arrow'])</span>@endif
            </{{ $tagName }}>
        @endforeach
    </div>
</div></section>
