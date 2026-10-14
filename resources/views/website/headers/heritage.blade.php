@php
    $extra = ['news', 'gallery'];
    $primary = $menu->reject(fn ($m) => in_array($m['slug'], $extra))->values();
    $alt = $menu->filter(fn ($m) => in_array($m['slug'], $extra))->values();
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
@endphp
<div class="top">
    <div class="wrap top-in">
        <div class="top-info">
            @if ($school->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a>@endif
            @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
        </div>
        <div class="top-links">
            @foreach ($alt as $item)<a href="{{ $item['url'] }}">{{ $item['label'] }}</a>@endforeach
            <a href="{{ $homeUrl }}/sign-in">Sign in</a>
            @if ($apply)<a href="{{ $apply }}">Apply</a>@endif
        </div>
    </div>
</div>

<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand')
        <button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
        <nav class="menu" id="menu" aria-label="Main">
            <div class="menu-main">
                @foreach ($primary as $item)
                    <a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif><span>{{ $item['label'] }}</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
                @endforeach
            </div>
            @if ($alt->isNotEmpty())
                <div class="menu-alt">
                    @foreach ($alt as $item)<a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>@endforeach
                </div>
            @endif
            <div class="menu-util">
                <a href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a>
                @if ($apply)<a href="{{ $apply }}">Apply online</a>@endif
                @if ($contactUrl)<a href="{{ $contactUrl }}">Contact us</a>@endif
            </div>
        </nav>
    </div>
</header>
