@php
    $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
@endphp
<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand', ['brandTagline' => true])
        <div class="hdr-tools">@include('website.partials.search-icon')<button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button></div>
        <nav class="menu" id="menu" aria-label="Main">
            <p class="menu-kick">{{ $school->name }}</p>
            <ol class="menu-list">
                @foreach ($menu as $item)
                    <li><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif><b>{{ $roman[$loop->index] ?? $loop->iteration }}</b><span>{{ $item['label'] }}</span></a></li>
                @endforeach
            </ol>
            <div class="menu-foot">
                <a class="menu-signin" href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a>
                @if ($apply)<a class="btn btn--solid" href="{{ $apply }}">Apply online</a>@endif
            </div>
        </nav>
    </div>
    @include('website.partials.search-bar')
</header>
