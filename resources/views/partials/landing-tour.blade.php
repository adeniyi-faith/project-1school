{{-- "See it in action": five short looping tours of the real app. Each tab shows three real screens (captured from the demo school) in a window that runs off the edge of its card, with a caption and a pause button. The pictures are small WebP files in public/images/landing, and only the tab being viewed is downloaded. Sized for phones first. All names and amounts are the demo school's sample data. --}}
@verbatim
<style>
/* ---------- See it in action ---------- */
.tour{margin-top:56px;scroll-margin-top:84px}
.tour-kicker{font-family:var(--mono);font-size:14px;letter-spacing:.14em;text-transform:uppercase;color:var(--label);text-align:center}
.tour h2.tour-h{font-size:clamp(28px,7.4vw,44px);line-height:1.2;font-weight:700;text-align:center;margin-top:12px;max-width:none}
.tour-sub{text-align:center;color:#B6C0DC;font-size:17px;margin:14px auto 0;max-width:34ch}
.tabs{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin:28px 0 24px}
.tabs button{font:600 16px/1 var(--display);color:#fff;background:rgba(255,255,255,.1);border:0;border-radius:999px;padding:0 22px;min-height:46px;cursor:pointer;transition:background .15s,color .15s}
.tabs button:hover{background:rgba(255,255,255,.18)}
.tabs button[aria-selected="true"]{background:#fff;color:var(--ink)}
.tourcard{background:#EEF1FA;border-radius:22px;color:var(--text);overflow:hidden;max-width:1020px;margin:0 auto}
.pn{display:grid}
.pn[hidden]{display:none}
/* The picture: a real app window that starts a little in from the left and runs off the right and bottom edges, like a window cropped on the page. */
.stagebox{position:relative;height:400px;overflow:hidden;background:radial-gradient(130% 80% at 0% 100%,var(--soft) 0 52%,transparent 52.4%)}
.dots{position:absolute;left:8px;top:96px;width:70px;height:210px;background-image:radial-gradient(var(--acc) 1.3px,transparent 1.6px);background-size:12px 12px;opacity:.28}
.win{position:absolute;left:30px;top:34px;width:640px;border-radius:14px 0 0 0;overflow:hidden;background:#fff;box-shadow:0 0 0 1px rgba(14,26,58,.1),0 30px 60px -24px rgba(12,23,56,.45);display:grid}
.win>.slide{grid-area:1/1;display:block;width:100%;height:auto;opacity:0;transition:opacity .6s ease;-webkit-user-drag:none}
.win>.slide.on{opacity:1;transform:translateX(var(--pan,-9%));transition:opacity .6s ease,transform 3.6s cubic-bezier(.3,0,.3,1)}
.cap{position:absolute;left:30px;bottom:16px;max-width:calc(100% - 100px);background:#0E1A3A;color:#fff;font:600 14px/1.3 var(--display);padding:9px 14px;border-radius:999px;box-shadow:0 10px 22px -10px rgba(12,23,56,.6);transition:opacity .3s}
.pausebtn{position:absolute;right:16px;bottom:14px;width:44px;height:44px;border-radius:50%;border:2px solid var(--ink);background:rgba(255,255,255,.85);color:var(--ink);display:grid;place-items:center;cursor:pointer;padding:0}
.pausebtn svg{width:17px;height:17px}
.pausebtn .i-play{display:none}
.pausebtn[aria-pressed="true"] .i-play{display:block}
.pausebtn[aria-pressed="true"] .i-pause{display:none}
.pn-text{padding:26px 24px 30px}
.pn-icon{width:64px;height:64px;border-radius:18px;background:#fff;display:grid;place-items:center;color:var(--acc);box-shadow:0 10px 24px -12px rgba(12,23,56,.35)}
.pn-icon svg{width:32px;height:32px}
.pn-text h3{font-size:clamp(25px,6.6vw,32px);line-height:1.2;font-weight:600;margin-top:22px}
.pn-text p{font-size:18px;line-height:1.55;color:#33405F;margin-top:12px}
.pn-text .btn{margin-top:26px;width:100%;background:var(--acc);color:#fff;border-radius:6px}
.pn-text .btn:hover{filter:brightness(1.08)}
.tour-note{font-size:13px;color:#8E9ABD;text-align:center;margin-top:16px}
@media (min-width:640px){.stagebox{height:440px}.win{width:760px;top:44px;left:44px}.cap{left:44px}.dots{left:14px;top:130px}}
@media (min-width:900px){
  .tour{margin-top:72px}
  .pn{grid-template-columns:1.25fr 1fr;align-items:stretch}
  .stagebox{height:auto;min-height:480px}
  .win{width:800px;top:56px;left:52px}
  .cap{left:52px;bottom:22px}
  .pausebtn{bottom:20px;right:20px}
  .pn-text{padding:48px 52px 48px 24px;align-self:center}
  .pn-text .btn{width:auto;display:inline-flex}
}
@media (prefers-reduced-motion:reduce){.win>.slide,.win>.slide.on{transition:none;transform:none}}
</style>
<noscript><style>.pausebtn,.cap{display:none}</style></noscript>
@endverbatim

<div class="tour" id="tour">
  <p class="tour-kicker">See it in action</p>
  <h2 class="tour-h">A school day, start to finish</h2>
  <p class="tour-sub">Tap a topic to watch the real app being used.</p>
  <div class="tabs" role="tablist" aria-label="Choose what to watch"><button type="button" role="tab" id="tt-fees" aria-selected="true" aria-controls="tp-fees">Fees</button><button type="button" role="tab" id="tt-notes" aria-selected="false" aria-controls="tp-notes" tabindex="-1">Lesson notes</button><button type="button" role="tab" id="tt-reports" aria-selected="false" aria-controls="tp-reports" tabindex="-1">Report cards</button><button type="button" role="tab" id="tt-parents" aria-selected="false" aria-controls="tp-parents" tabindex="-1">Parents</button><button type="button" role="tab" id="tt-cbt" aria-selected="false" aria-controls="tp-cbt" tabindex="-1">CBT exams</button></div>
  <div class="tourcard">
    <div class="pn" role="tabpanel" id="tp-fees" aria-labelledby="tt-fees" style="--acc:#3346E0;--soft:#E3E8FF">
      <div class="stagebox" data-ms="3400">
        <i class="dots" aria-hidden="true"></i>
        <div class="win"><img class="slide on" src="{{ asset('images/landing/fees-invoices.webp') }}" width="1300" height="851" alt="Invoices list with amounts collected and still owed" data-cap="Who has paid, who owes" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/fees-payment.webp') }}" width="1300" height="851" alt="Dialog to record a payment by searching for a student" data-cap="Take a payment" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/fees-plans.webp') }}" width="1300" height="851" alt="Saved payment plans such as three instalments" data-cap="Split fees in instalments" loading="lazy" decoding="async"></div>
        <span class="cap" aria-live="off"></span>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="M3 10h18M7 15h4"/></svg></div>
        <h3>Fees that tick themselves off</h3>
        <p>A parent pays by card or transfer. The invoice marks itself paid and the bursar's totals update. No chasing receipts.</p>
        <a href="#p-fees" class="btn">Explore fees</a>
      </div>
    </div>

    <div class="pn" role="tabpanel" id="tp-notes" aria-labelledby="tt-notes" style="--acc:#B9770E;--soft:#FBEBCB" hidden>
      <div class="stagebox" data-ms="3400">
        <i class="dots" aria-hidden="true"></i>
        <div class="win"><img class="slide on" src="{{ asset('images/landing/notes-list.webp') }}" width="1300" height="851" alt="List of lesson notes marked waiting, approved or sent back" data-cap="Every note and its status" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/notes-new.webp') }}" width="1300" height="851" alt="New lesson note form" data-cap="Pick the week and topic" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/notes-approved.webp') }}" width="1300" height="851" alt="An approved lesson note" data-cap="Approved and ready to print" loading="lazy" decoding="async"></div>
        <span class="cap" aria-live="off"></span>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/></svg></div>
        <h3>Lesson notes in minutes</h3>
        <p>Pick the topic and the AI drafts the note. The teacher reads it, changes anything, and sends it for approval.</p>
        <a href="#p-ai" class="btn">Explore lesson notes</a>
      </div>
    </div>

    <div class="pn" role="tabpanel" id="tp-reports" aria-labelledby="tt-reports" style="--acc:#D4442F;--soft:#FDE2DC" hidden>
      <div class="stagebox" data-ms="3400">
        <i class="dots" aria-hidden="true"></i>
        <div class="win"><img class="slide on" src="{{ asset('images/landing/reports-results.webp') }}" width="1300" height="851" alt="Term results by class with averages" data-cap="Every class, every term" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/reports-scores.webp') }}" width="1300" height="851" alt="Score entry for one class with totals, grades and positions" data-cap="Type scores, grades appear" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/reports-print.webp') }}" width="1300" height="851" alt="Print report cards page" data-cap="Print a whole class at once" loading="lazy" decoding="async"></div>
        <span class="cap" aria-live="off"></span>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div>
        <h3>Scores in, report cards out</h3>
        <p>Teachers type the scores. Totals, grades and class positions are worked out for you, then the report card goes to parents.</p>
        <a href="#p-ops" class="btn">Explore report cards</a>
      </div>
    </div>

    <div class="pn" role="tabpanel" id="tp-parents" aria-labelledby="tt-parents" style="--acc:#0F8F80;--soft:#D9F3EE" hidden>
      <div class="stagebox" data-ms="3400">
        <i class="dots" aria-hidden="true"></i>
        <div class="win"><img class="slide on" src="{{ asset('images/landing/parents-home.webp') }}" width="1300" height="851" alt="Parent home with fees, attendance and latest results" data-cap="Fees, results, attendance" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/parents-results.webp') }}" width="1300" height="851" alt="A child's term results" data-cap="Every subject and grade" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/parents-fees.webp') }}" width="1300" height="851" alt="A child's fees paid and balance" data-cap="What was paid, what is left" loading="lazy" decoding="async"></div>
        <span class="cap" aria-live="off"></span>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg></div>
        <h3>Parents see it all in one place</h3>
        <p>Results, fees and attendance for every child, right in the phone's browser. Nothing to install, and one login covers every child.</p>
        <a href="#p-portals" class="btn">Explore the parent portal</a>
      </div>
    </div>

    <div class="pn" role="tabpanel" id="tp-cbt" aria-labelledby="tt-cbt" style="--acc:#5B4BD6;--soft:#E6E2FB" hidden>
      <div class="stagebox" data-ms="3400">
        <i class="dots" aria-hidden="true"></i>
        <div class="win"><img class="slide on" src="{{ asset('images/landing/cbt-banks.webp') }}" width="1300" height="851" alt="Question banks by subject" data-cap="Question banks by subject" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/cbt-tests.webp') }}" width="1300" height="851" alt="List of computer tests with their status" data-cap="Timed tests for any class" loading="lazy" decoding="async"><img class="slide" src="{{ asset('images/landing/cbt-student.webp') }}" width="1300" height="851" alt="A student's My tests page" data-cap="Students open their tests" loading="lazy" decoding="async"></div>
        <span class="cap" aria-live="off"></span>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4M8 10.5l2.5 2.5L16 8"/></svg></div>
        <h3>Exams on a phone or computer</h3>
        <p>Students answer on screen. Multiple choice questions are marked the moment they hand in, and teachers mark the written ones.</p>
        <a href="#p-ai" class="btn">Explore CBT exams</a>
      </div>
    </div>
  </div>
  <p class="tour-note">Screens from our demo school. All names and amounts are sample data.</p>
</div>

@verbatim
<script>
(function(){
  var root=document.getElementById('tour');if(!root)return;
  var tabs=[].slice.call(root.querySelectorAll('[role=tab]')),
      panels=tabs.map(function(t){return document.getElementById(t.getAttribute('aria-controls'))}),
      buttons=[].slice.call(root.querySelectorAll('.pausebtn')),
      reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var playing=!reduce,visible=false,cur=0,n=0,timer=0;

  function slides(i){return panels[i].querySelectorAll('.slide')}
  function cap(i){return panels[i].querySelector('.cap')}
  function setSlide(i,k){
    var s=slides(i);
    [].forEach.call(s,function(e,j){e.classList.toggle('on',j===k)});
    cap(i).textContent=s[k].dataset.cap;
  }
  function stop(){clearTimeout(timer)}
  function schedule(){
    stop();
    if(!playing||!visible||document.hidden)return;
    var ms=+panels[cur].querySelector('.stagebox').dataset.ms||3400;
    timer=setTimeout(function(){n=(n+1)%slides(cur).length;setSlide(cur,n);schedule()},ms);
  }
  function show(i){
    stop();cur=i;n=0;
    tabs.forEach(function(t,k){var on=k===i;t.setAttribute('aria-selected',on);t.tabIndex=on?0:-1;panels[k].hidden=!on});
    setSlide(i,0);schedule();
  }
  function setPlaying(p){
    playing=p;
    buttons.forEach(function(b){b.setAttribute('aria-pressed',!p);b.setAttribute('aria-label',p?'Pause animation':'Play animation')});
    schedule();
  }
  tabs.forEach(function(t,i){
    t.addEventListener('click',function(){show(i)});
    t.addEventListener('keydown',function(e){
      var k=e.key==='ArrowRight'?1:e.key==='ArrowLeft'?-1:0;if(!k)return;
      e.preventDefault();var j=(i+k+tabs.length)%tabs.length;show(j);tabs[j].focus();
    });
  });
  buttons.forEach(function(b){
    b.setAttribute('aria-pressed',!playing);b.setAttribute('aria-label',playing?'Pause animation':'Play animation');
    b.addEventListener('click',function(){setPlaying(!playing)});
  });
  document.addEventListener('visibilitychange',schedule);
  if('IntersectionObserver' in window){
    new IntersectionObserver(function(es){visible=es[0].isIntersecting;schedule()},{threshold:.25}).observe(root.querySelector('.tourcard'));
  }else{visible=true}
  show(0);
})();
</script>
@endverbatim
