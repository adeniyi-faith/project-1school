@php
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
    $inquire = $apply ?: $contactUrl;
    $dots = '<svg class="wa-dots" viewBox="0 0 16 16" aria-hidden="true"><circle cx="4" cy="4" r="2"/><circle cx="12" cy="4" r="2"/><circle cx="4" cy="12" r="2"/><circle cx="12" cy="12" r="2"/></svg>';
@endphp
<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand')
        <div class="hdr-tools">
            <button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><span>Menu</span>{!! $dots !!}</button>
            @if ($inquire)<a class="wa-inq" href="{{ $inquire }}"><span>{{ $apply ? 'Apply' : 'Contact' }}</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zm0 1 8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg></a>@endif
        </div>
        <nav class="menu" id="menu" aria-label="Main">
            <div class="wrap wa-menu-in">
                <div class="menu-top">
                    @include('website.partials.brand')
                    <button class="menu-x" type="button" data-menu-close aria-label="Close menu"><span>Close</span><svg class="wa-dots" viewBox="0 0 16 16" aria-hidden="true"><path d="m3 3 10 10M13 3 3 13" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg></button>
                </div>
                <form class="wa-msearch" role="search" method="get" action="{{ $homeUrl }}/search">
                    <label class="sr" for="q-menu">Search this site</label>
                    <input id="q-menu" type="search" name="q" placeholder="Search" maxlength="80" autocomplete="off">
                    <button type="submit" aria-label="Search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="m16.5 16.5 4 4" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg></button>
                </form>
                <ul class="menu-list">
                    @foreach ($menu as $item)
                        <li><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
                <div class="wa-menu-foot">
                    <a class="menu-signin" href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a>
                    @if ($apply)<a class="btn btn--solid menu-apply" href="{{ $apply }}">Apply online</a>@endif
                </div>
            </div>
        </nav>
    </div>
</header>
