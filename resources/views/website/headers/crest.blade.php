@php
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
    $arrow = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
@endphp
<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand')
        <div class="hdr-tools">
            @include('website.partials.search-icon')
            <button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
        </div>
    </div>
    <nav class="menu" id="menu" aria-label="Main">
        <div class="cr-menu-in">
            <ul class="menu-list">
                @foreach ($menu as $item)
                    <li class="menu-item"><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif><span>{{ $item['label'] }}</span>{!! $arrow !!}</a>@include('website.partials.menu-sub')</li>
                @endforeach
            </ul>
            <ul class="cr-menu-small">
                <li><a href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a></li>
                @if ($apply)<li><a href="{{ $apply }}">Apply online</a></li>@endif
                @if ($school->phone)<li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $school->phone) }}">{{ $school->phone }}</a></li>@endif
            </ul>
        </div>
    </nav>
    @include('website.partials.search-bar')
</header>
