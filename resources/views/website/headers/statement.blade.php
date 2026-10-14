<header class="hdr">
    <div class="wrap hdr-in">
        @include('website.partials.brand', ['brandTagline' => true])
        <div class="hdr-tools">@include('website.partials.search-icon')<button class="burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button></div>
        @include('website.partials.nav')
    </div>
</header>
@include('website.partials.search-bar')
