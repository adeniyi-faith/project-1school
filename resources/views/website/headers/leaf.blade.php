@php
    $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null;
    $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null;
@endphp
<header class="hdr">
    <div class="wrap hdr-in lf-bar">
        @include('website.partials.brand')
        <div class="lf-tools">
            @include('website.partials.search-icon')
            <button class="burger lf-burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="menu"><i></i><i></i><i></i></button>
        </div>
        <nav class="menu lf-drawer" id="menu" aria-label="Main">
            <div class="lf-drawer-top">
                @include('website.partials.brand', ['inMenu' => true])
                <button class="lf-x" type="button" data-menu-close aria-label="Close menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg></button>
            </div>
            <ul class="menu-list">
                @foreach ($menu as $item)
                    <li class="menu-item"><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a>@include('website.partials.menu-sub')</li>
                @endforeach
                <li class="menu-item lf-signin"><a class="menu-signin" href="{{ $homeUrl }}/sign-in" @if (($current ?? '') === 'sign-in') aria-current="page" @endif>Sign in</a></li>
                @if ($apply)<li class="menu-item lf-apply"><a class="btn btn--solid" href="{{ $apply }}">Apply online</a></li>@endif
            </ul>
            <div class="lf-drawer-foot">
                @if ($school->phone)<a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a>@endif
                @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
                @if (count($social))
                    <ul class="social">
                        @foreach ($social as $key => $href)
                            <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </nav>
        <button class="lf-scrim" type="button" data-menu-close tabindex="-1" aria-hidden="true"></button>
    </div>
    @include('website.partials.search-bar')
</header>
<script>
(function(){
  // a long menu on a laptop would squeeze the school name, so it uses the slide-in menu instead
  var b=document.body,bar=document.querySelector('.lf-bar'),nm=bar&&bar.querySelector('.brand span');
  function fit(){
    b.classList.remove('lf-compact');
    if(!bar||!nm||window.innerWidth<1152)return;
    var lh=parseFloat(getComputedStyle(nm).lineHeight)||20;
    if(bar.scrollWidth>bar.clientWidth+1||nm.getBoundingClientRect().height>lh*2.4){b.classList.add('lf-compact');}
  }
  fit();window.addEventListener('resize',fit);window.addEventListener('load',fit);
  if(document.fonts&&document.fonts.ready){document.fonts.ready.then(fit);}
})();
</script>
