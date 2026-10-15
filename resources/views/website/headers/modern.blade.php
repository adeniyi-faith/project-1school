@php $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null; @endphp
<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand')
        <div class="hdr-tools">@include('website.partials.search-icon')<button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button></div>
        <nav class="menu" id="menu" aria-label="Main">
            <div class="menu-top">
                @include('website.partials.brand')
                <button class="menu-x" type="button" data-menu-close aria-label="Close menu">X</button>
            </div>
            <ul class="menu-list">
                @foreach ($menu as $item)
                    <li><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif><span>{{ $item['label'] }}</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
                @endforeach
                <li class="menu-extra"><a href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif><span>Sign in</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
            </ul>
            @if ($apply)<a class="btn btn--solid menu-apply" href="{{ $apply }}">Apply online</a>@endif
        </nav>
    </div>
    @include('website.partials.search-bar')
</header>
