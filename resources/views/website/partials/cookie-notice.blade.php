{{-- The cookie box at the bottom of every public page. Shown once per visitor (remembered in the browser); a school can switch it off in Website > Settings. --}}
@if ($site->option('cookie_notice') && ! $preview)
<aside class="cookie" id="cookie" role="region" aria-label="Cookie notice" hidden>
    <div class="cookie-in">
        <p class="cookie-title">Cookies</p>
        <p class="cookie-text">We use a few cookies to keep you signed in and make this website work. We do not use advertising or tracking cookies.@if ($privacyUrl) <a href="{{ $privacyUrl }}">Read our Privacy Policy</a>.@endif</p>
        <button class="cookie-ok" type="button">Accept</button>
    </div>
</aside>
<script>
(function(){
  var box=document.getElementById('cookie');if(!box)return;
  var key='cookie-ok-{{ $school->id }}';
  try{if(localStorage.getItem(key)==='1')return;}catch(e){}
  box.hidden=false;document.body.classList.add('cookie-open');
  box.querySelector('.cookie-ok').addEventListener('click',function(){box.hidden=true;document.body.classList.remove('cookie-open');try{localStorage.setItem(key,'1');}catch(e){}});
})();
</script>
@endif
