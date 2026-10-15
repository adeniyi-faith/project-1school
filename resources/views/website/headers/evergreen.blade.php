@php
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
    $arrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
@endphp
<header class="hdr">
    <div class="eg-bar">
        <button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
        @include('website.partials.brand')
        @include('website.partials.search-icon')
    </div>
    <nav class="menu" id="menu" aria-label="Main">
        <div class="eg-menu-in">
            <ul class="menu-list">
                @foreach ($menu as $item)
                    <li class="menu-item"><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif><span>{{ $item['label'] }}</span>{!! $arrow !!}</a>@include('website.partials.menu-sub')</li>
                @endforeach
            </ul>
            <ul class="eg-menu-small">
                <li><a href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a></li>
                @if ($apply)<li><a href="{{ $apply }}">Apply online</a></li>@endif
                @if ($school->phone)<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></li>@endif
            </ul>
            @if (count($social))
                <ul class="social eg-menu-social">
                    @foreach ($social as $key => $href)
                        <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                    @endforeach
                </ul>
            @endif
        </div>
    </nav>
    <div class="sbar" id="sbar" hidden>
        <form class="eg-search" role="search" method="get" action="{{ $homeUrl }}/search">
            <label for="sbar-q">Keyword</label>
            <input id="sbar-q" type="search" name="q" value="{{ $query ?? '' }}" maxlength="80" autocomplete="off">
            <button type="submit" class="btn btn--solid">Search</button>
        </form>
    </div>
</header>
