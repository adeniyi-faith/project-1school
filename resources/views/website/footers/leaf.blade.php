@php $phoneDigits = $school->phone ? preg_replace('/[^0-9+]/', '', $school->phone) : null; $apply = $site->option('apply_button') ? route('public.admission.show', $school) : null; @endphp
<footer class="ftr ftr--leaf">
    <div class="wrap">
        <div class="lf-f-grid">
            <div class="lf-f-id">
                <a href="{{ $homeUrl }}" class="lf-f-name">@if ($logo)<img class="{{ $logoFit->classes('ftr') }}" src="{{ $logo }}" alt="" width="64" height="64" loading="lazy">@endif<span>{{ $school->name }}</span></a>
                @if ($site->tagline)<p class="lf-f-line">{{ $site->tagline }}</p>@endif
                @if (count($social))
                    <ul class="social">
                        @foreach ($social as $key => $href)
                            <li><a href="{{ $href }}" rel="noopener me" target="_blank" aria-label="{{ \App\Website\SiteRenderer::SOCIAL[$key] ?? $key }}">@include('website.partials.social', ['name' => $key])</a></li>
                        @endforeach
                    </ul>
                @endif
                @if ($apply)<a class="btn btn--solid lf-f-apply" href="{{ $apply }}">Apply online</a>@endif
            </div>
            <div>
                <h3>Visit us</h3>
                <address>@if ($place){{ $place }}@else Ask the school office for directions.@endif</address>
            </div>
            <div>
                <h3>Talk to us</h3>
                <address>
                    @if ($school->phone)<a href="tel:{{ $phoneDigits }}">{{ $school->phone }}</a><br>@endif
                    @if ($school->email)<a href="mailto:{{ $school->email }}">{{ $school->email }}</a>@endif
                </address>
            </div>
            <div>
                <h3>Explore</h3>
                <ul class="lf-f-links">
                    @foreach ($menu->take(6) as $item)<li><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>@endforeach
                    <li><a href="{{ $homeUrl }}/sign-in">Sign in</a></li>
                </ul>
            </div>
        </div>
        <div class="legal">
            <p>@if ($site->footer_text){{ $site->footer_text }} &middot; @endif&copy; {{ now()->year }} {{ $school->name }}@if ($privacyUrl) &middot; <a href="{{ $privacyUrl }}">Privacy Policy</a>@endif</p>
            <p class="powered">School platform powered by <a href="{{ url('/') }}" rel="noopener">SchoolRuns</a></p>
        </div>
    </div>
</footer>
<button class="lf-top" type="button" aria-label="Back to the top" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 14 6-6 6 6" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
<script>
(function(){
  // a round button that fills as the visitor scrolls and takes them back to the top
  var t=document.querySelector('.lf-top');if(!t)return;
  var de=document.documentElement,ticking=false;
  function upd(){
    ticking=false;
    var max=de.scrollHeight-window.innerHeight,y=window.scrollY||de.scrollTop;
    t.style.setProperty('--p',max>0?Math.min(100,Math.round(y/max*100)):0);
    if(y>420){t.hidden=false;t.classList.add('on');}else{t.classList.remove('on');}
  }
  window.addEventListener('scroll',function(){if(!ticking){ticking=true;window.requestAnimationFrame(upd);}},{passive:true});
  t.addEventListener('click',function(){window.scrollTo({top:0,behavior:window.matchMedia('(prefers-reduced-motion:reduce)').matches?'auto':'smooth'});});
  upd();
})();
</script>
