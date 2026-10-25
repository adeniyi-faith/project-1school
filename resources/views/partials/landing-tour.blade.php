{{-- "See it in action": five short looping app scenes built from plain HTML and CSS (no video or image files, so the section costs almost no data). Sized for phones first: everything inside a scene scales with the scene's own width (cqw units). The script below plays one scene at a time, pauses when off screen, and shows the finished frame when the visitor asks for reduced motion. All people and amounts are made up. --}}
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
.tourcard{background:#EEF1FA;border-radius:22px;color:var(--text);overflow:hidden;max-width:980px;margin:0 auto}
.pn{display:grid}
.pn[hidden]{display:none}
.stagebox{position:relative;padding:30px 16px 72px;display:grid;grid-template-columns:minmax(0,1fr);justify-items:center;background:radial-gradient(120% 70% at 0% 100%,var(--soft) 0 46%,transparent 46.4%)}
.pn-text{padding:6px 24px 30px}
.pn-icon{width:60px;height:60px;border-radius:18px;background:#fff;display:grid;place-items:center;color:var(--acc);box-shadow:0 10px 24px -12px rgba(12,23,56,.35)}
.pn-icon svg{width:30px;height:30px}
.pn-text h3{font-size:clamp(24px,6.4vw,30px);line-height:1.2;font-weight:600;margin-top:20px}
.pn-text p{font-size:18px;line-height:1.55;color:#33405F;margin-top:12px}
.pn-text .btn{margin-top:24px;width:100%;background:var(--acc);color:#fff;border-radius:12px}
.pn-text .btn:hover{filter:brightness(1.08)}
.pausebtn{position:absolute;right:18px;bottom:14px;width:46px;height:46px;border-radius:50%;border:2px solid var(--ink);background:rgba(255,255,255,.92);color:var(--ink);display:grid;place-items:center;cursor:pointer;padding:0}
.pausebtn svg{width:18px;height:18px}
.pausebtn .i-play{display:none}
.pausebtn[aria-pressed="true"] .i-play{display:block}
.pausebtn[aria-pressed="true"] .i-pause{display:none}
.tour-note{font-size:13px;color:#8E9ABD;text-align:center;margin-top:16px}

/* The scene: a phone-shaped app screen. 1cqw = 1% of its own width, so text and spacing shrink and grow together. */
.scene{width:330px;max-width:100%;container-type:inline-size}
.app{position:relative;overflow:hidden;background:#fff;border:1.8cqw solid #0E1A3A;border-radius:9cqw;aspect-ratio:5/6.5;font-size:4.2cqw;line-height:1.3;box-shadow:0 26px 50px -22px rgba(12,23,56,.55);user-select:none;-webkit-user-select:none}
.app *{box-sizing:border-box}
.app b{font-weight:600}
.app .bar{display:flex;justify-content:space-between;align-items:center;gap:2cqw;padding:3.4cqw 4cqw;border-bottom:.4cqw solid var(--line);font-size:4.6cqw}
.app .bar b{font-family:var(--display);font-weight:700}
.app .chip{font-size:3.4cqw;font-weight:600;background:var(--soft);color:var(--acc);border-radius:99cqw;padding:.8cqw 2.6cqw;white-space:nowrap;font-variant-numeric:tabular-nums}
.app .body{padding:3.4cqw 4cqw;display:grid;gap:2.6cqw;align-content:start}
.app small{display:block;font-size:3.5cqw;color:var(--muted);line-height:1.25}
.app .slot,.app .screens{display:grid}
.app .slot>*,.app .screens>*{grid-area:1/1}
.app .tiles{display:grid;grid-template-columns:1fr 1fr;gap:2.6cqw}
.app .tile{background:var(--mist);border-radius:3cqw;padding:2.6cqw 3cqw}
.app .tile b{font-family:var(--display);font-size:5.4cqw;font-variant-numeric:tabular-nums}
.app .g{color:#0B7568}.app .r{color:#AE3726}
.app .p{display:inline-block;font-size:3.4cqw;font-weight:600;border-radius:99cqw;padding:.7cqw 2.4cqw;white-space:nowrap}
.app .p.ok{background:#DDF4F0;color:#0B7568}.app .p.part{background:#FBEED6;color:#8F610E}.app .p.due{background:#FCE3DE;color:#AE3726}.app .p.acc{background:var(--soft);color:var(--acc)}
.app .li{position:relative;display:grid;grid-template-columns:1fr auto;gap:2cqw;align-items:center;padding:2.6cqw 0;border-top:.4cqw solid var(--line)}
.app .li:first-child{border-top:0}
.app .li div:last-child{text-align:right}
.app .amt{font-weight:600;font-variant-numeric:tabular-nums;display:block;margin-bottom:.6cqw}
.app .btnx{display:flex;align-items:center;justify-content:center;position:relative;background:var(--acc);color:#fff;font-weight:600;border-radius:3cqw;min-height:10.5cqw}
.app .toast{position:absolute;left:3cqw;right:3cqw;top:13cqw;z-index:3;background:#0E1A3A;color:#fff;border-radius:3.4cqw;padding:3cqw 3.6cqw;display:flex;gap:3cqw;align-items:center;box-shadow:0 8cqw 12cqw -6cqw rgba(12,23,56,.55)}
.app .toast small{color:#B6C0DC}
.app .dot{flex:none;width:7cqw;height:7cqw;border-radius:50%;background:#0F9F8F;display:grid;place-items:center}
.app .dot svg{width:4cqw;height:4cqw}
.app .flash{position:absolute;inset:.6cqw -2cqw;border-radius:2.4cqw;background:rgba(15,159,143,.12);pointer-events:none;z-index:0}
.app .li>*:not(.flash){position:relative}
.app .note{display:flex;gap:2.4cqw;align-items:center;background:#EDFAF7;color:#0B7568;border-radius:3cqw;padding:2.8cqw 3cqw;font-weight:600}
.app .note svg{width:4.4cqw;height:4.4cqw;flex:none}
.app .fld{background:var(--mist);border-radius:3cqw;padding:2cqw 3cqw;min-width:0}
.app .fld b{display:block;font-size:4.2cqw;min-height:5.6cqw;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.app .fields{display:grid;grid-template-columns:1fr 1fr;gap:2.4cqw}
.app .paper{background:#FFFCF6;border:.4cqw solid #F1E4C6;border-radius:3cqw;padding:3cqw 3.4cqw;display:grid;gap:1.8cqw}
.app .paper h5{margin:0;font-family:var(--display);font-size:3.9cqw;color:#8F610E}
.app .paper p{font-size:3.9cqw;line-height:1.35;color:#2A3555;min-height:10.8cqw}
.app .cur::after{content:"";display:inline-block;width:.8cqw;height:4cqw;background:var(--acc);margin-left:.6cqw;vertical-align:-.6cqw;animation:tourblink 1s steps(2) infinite}
.app .grid6{display:grid;grid-template-columns:1.55fr .75fr .75fr .85fr 1fr 1fr;gap:0 1cqw;align-items:center;font-variant-numeric:tabular-nums;font-size:4cqw}
.app .grid6.h{font-size:3.3cqw;color:var(--muted);font-weight:600;padding-bottom:1.2cqw;border-bottom:.4cqw solid var(--line)}
.app .grid6.rw{padding:2.4cqw 0;border-bottom:.4cqw solid var(--line)}
.app .grid6 .nm{font-weight:600;white-space:nowrap}
.app .sheet{position:absolute;left:3cqw;right:3cqw;bottom:3cqw;z-index:3;background:#fff;border-radius:4cqw;border:.5cqw solid var(--line);padding:3.4cqw 3.8cqw;box-shadow:0 -4cqw 12cqw -4cqw rgba(12,23,56,.45);display:grid;gap:1.8cqw}
.app .sheet .row2{display:flex;justify-content:space-between;gap:2cqw;font-size:4cqw}
.app .sheet .row2 span:last-child{font-weight:700}
.app .avatar2{width:10cqw;height:10cqw;border-radius:50%;background:var(--acc);color:#fff;display:grid;place-items:center;font-weight:700;font-size:3.8cqw;flex:none}
.app .hello{display:flex;gap:3cqw;align-items:center}
.app .kids{display:flex;gap:2cqw}
.app .kids span{font-size:3.7cqw;font-weight:600;border-radius:99cqw;padding:1.2cqw 3.2cqw;background:var(--mist);color:var(--muted)}
.app .kids span.on2{background:var(--acc);color:#fff}
.app .banner{position:relative;display:flex;gap:2.8cqw;align-items:center;background:var(--soft);border-radius:3.4cqw;padding:3cqw 3.2cqw;font-weight:600}
.app .tnav{position:absolute;left:0;right:0;bottom:0;display:grid;grid-template-columns:repeat(3,1fr);border-top:.4cqw solid var(--line);background:#fff;z-index:2}
.app .tnav span{position:relative;text-align:center;padding:3cqw 0 3.4cqw;font-size:3.7cqw;font-weight:600;color:var(--muted)}
.app .tnav u{position:absolute;left:28%;right:28%;top:-.4cqw;height:.9cqw;border-radius:9cqw;background:var(--acc);text-decoration:none}
.app .scr{padding-bottom:16cqw}
.app .big{font-family:var(--display);font-weight:700;font-size:8cqw;line-height:1.1}
.app .opt{position:relative;display:flex;gap:3cqw;align-items:center;border:.5cqw solid var(--line);border-radius:3cqw;padding:2.6cqw 3cqw;font-weight:500}
.app .opt em{font-style:normal;font-weight:700;color:var(--muted);width:5cqw}
.app .opt i.pick{position:absolute;inset:-.5cqw;border:.7cqw solid var(--acc);background:var(--soft);border-radius:3cqw;z-index:0}
.app .opt>*:not(.pick):not(.tap){position:relative;z-index:1}
.app .qtext{font-family:var(--display);font-weight:600;font-size:4.7cqw;line-height:1.3;margin-bottom:.6cqw}
.app .prog{height:1.4cqw;border-radius:9cqw;background:var(--line);overflow:hidden}
.app .prog i{display:block;height:100%;background:var(--acc);border-radius:9cqw}
.app .result{display:grid;gap:2.4cqw;justify-items:center;text-align:center;padding-top:5cqw}
.app .ring{width:26cqw;height:26cqw;border-radius:50%;background:conic-gradient(var(--acc) 0 90%,var(--line) 90% 100%);display:grid;place-items:center}
.app .ring span{width:20cqw;height:20cqw;border-radius:50%;background:#fff;display:grid;place-items:center;font-family:var(--display);font-weight:700;font-size:7.4cqw}

/* Things that appear step by step. data-at = the step it appears; data-out = the step it goes away. */
.app [data-at]{opacity:0;transform:translateY(1.6cqw);transition:opacity .35s ease,transform .35s ease}
.app [data-at].on{opacity:1;transform:none}
.app [data-out]{transition:opacity .3s ease,transform .35s ease}
.app [data-out].off{opacity:0!important;pointer-events:none}
.app .tap{position:absolute;right:5cqw;bottom:-1.5cqw;width:7cqw;height:7cqw;border-radius:50%;background:rgba(14,26,58,.22);border:.6cqw solid rgba(14,26,58,.55);z-index:4;pointer-events:none;animation:tourtap 1s ease-out infinite}
@keyframes tourtap{0%{box-shadow:0 0 0 0 rgba(14,26,58,.35);transform:scale(.8)}60%{transform:scale(1)}100%{box-shadow:0 0 0 4cqw rgba(14,26,58,0)}}
@keyframes tourblink{50%{opacity:0}}

@media (min-width:900px){
  .tour{margin-top:72px}
  .pn{grid-template-columns:1.05fr 1fr;align-items:center}
  .stagebox{padding:44px 24px 76px;min-height:100%}
  .pn-text{padding:40px 56px 40px 24px}
  .pn-text .btn{width:auto;display:inline-flex}
  .scene{width:340px}
}
@media (prefers-reduced-motion:reduce){.app .tap{animation:none}.app .cur::after{animation:none}}
</style>
<noscript><style>.app [data-at]{opacity:1;transform:none}.app .tap{display:none}.pausebtn{display:none}</style></noscript>
@endverbatim

<div class="tour" id="tour">
  <p class="tour-kicker">See it in action</p>
  <h2 class="tour-h">A school day, start to finish</h2>
  <p class="tour-sub">Tap a topic to watch the app being used on a phone.</p>

  @verbatim
  <div class="tabs" role="tablist" aria-label="Choose what to watch">
    <button type="button" role="tab" id="tt-fees" aria-selected="true" aria-controls="tp-fees">Fees</button>
    <button type="button" role="tab" id="tt-notes" aria-selected="false" aria-controls="tp-notes" tabindex="-1">Lesson notes</button>
    <button type="button" role="tab" id="tt-reports" aria-selected="false" aria-controls="tp-reports" tabindex="-1">Report cards</button>
    <button type="button" role="tab" id="tt-parents" aria-selected="false" aria-controls="tp-parents" tabindex="-1">Parents</button>
    <button type="button" role="tab" id="tt-cbt" aria-selected="false" aria-controls="tp-cbt" tabindex="-1">CBT exams</button>
  </div>

  <div class="tourcard">

    <!-- ===== Fees ===== -->
    <div class="pn" role="tabpanel" id="tp-fees" aria-labelledby="tt-fees" style="--acc:#3346E0;--soft:#E3E8FF">
      <div class="stagebox">
        <div class="scene" data-ms="1300" data-hold="3200" role="img" aria-label="Animation: a parent pays a school fee by card, and the bursar's invoice list changes from Part paid to Paid and a receipt is created.">
          <div class="app">
            <div class="bar"><b>Invoices</b><span class="chip">1st term</span></div>
            <div class="body">
              <div class="tiles">
                <div class="tile"><small>Collected</small><div class="slot"><b data-out="4">₦48.2m</b><b class="g" data-at="4">₦48.3m</b></div></div>
                <div class="tile"><small>Still owed</small><div class="slot"><b data-out="4">₦18.7m</b><b class="r" data-at="4">₦18.6m</b></div></div>
              </div>
              <div>
                <div class="li"><i class="flash" data-at="4" data-out="6"></i><div><b>Adaeze Okafor</b><small>JSS 2B · Tuition</small></div><div><span class="amt">₦185,000</span><span class="slot"><span class="p part" data-out="4">Part paid</span><span class="p ok" data-at="4">Paid</span></span></div></div>
                <div class="li"><div><b>Tunde Bello</b><small>SS 1A · Tuition</small></div><div><span class="amt">₦96,500</span><span class="p due">Owing</span></div></div>
                <div class="li"><div><b>Hadiza Musa</b><small>Primary 4 · Uniform</small></div><div><span class="amt">₦32,000</span><span class="p ok">Paid</span></div></div>
                <div class="li"><div><b>Chidi Eze</b><small>JSS 1A · Bus</small></div><div><span class="amt">₦40,000</span><span class="p part">Part paid</span></div></div>
              </div>
              <div class="note" data-at="5"><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 6.5l2.4 2.4 4.6-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Receipt created. Nothing to type.</div>
            </div>
            <div class="toast" data-at="2" data-out="5"><span class="dot"><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 6.5l2.4 2.4 4.6-5" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><b>₦125,000 received</b><small>Paid by card · Adaeze Okafor</small></div></div>
          </div>
        </div>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="3"/><path d="M3 10h18M7 15h4"/></svg></div>
        <h3>Fees that tick themselves off</h3>
        <p>A parent pays by card or transfer. The invoice marks itself paid and the bursar's totals update. No chasing receipts.</p>
        <a href="#p-fees" class="btn">Explore fees</a>
      </div>
    </div>

    <!-- ===== Lesson notes ===== -->
    <div class="pn" role="tabpanel" id="tp-notes" aria-labelledby="tt-notes" style="--acc:#B9770E;--soft:#FBEBCB" hidden>
      <div class="stagebox">
        <div class="scene" data-ms="1400" data-hold="3200" role="img" aria-label="Animation: a teacher picks a subject, class and topic, taps Write with AI, a lesson note is drafted, and the head of department approves it.">
          <div class="app">
            <div class="bar"><b>New lesson note</b><span class="chip">Teacher</span></div>
            <div class="body">
              <div class="fields">
                <div class="fld"><small>Subject</small><b>Basic Science</b></div>
                <div class="fld"><small>Class</small><b>JSS 1</b></div>
                <div class="fld"><small>Term · Week</small><b data-at="1">1st · Week 4</b></div>
                <div class="fld"><small>Topic</small><b data-at="1" data-type="States of matter"></b></div>
              </div>
              <div class="btnx"><span class="slot"><span data-out="3">Write with AI</span><span data-at="3" data-out="6">Writing the note…</span><span data-at="6">Draft ready. Edit anything.</span></span><i class="tap" data-at="2" data-out="3"></i></div>
              <div class="paper" data-at="3">
                <h5>Objectives</h5>
                <p data-at="4" data-type="Name the three states of matter and give two examples of each."></p>
                <h5>Introduction</h5>
                <p data-at="5" data-type="Show ice, water and steam from a kettle. Ask: what changed?"></p>
              </div>
              <div class="slot" style="min-height:10.5cqw">
                <div class="btnx" data-at="6" data-out="8" style="background:#0E1A3A">Send for approval<i class="tap" data-at="6" data-out="7"></i></div>
                <div class="note" data-at="8"><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 6.5l2.4 2.4 4.6-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Approved by Head of Department</div>
              </div>
            </div>
          </div>
        </div>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 12h7M9 16h7"/></svg></div>
        <h3>Lesson notes in minutes</h3>
        <p>Pick the topic and the AI drafts the note. The teacher reads it, changes anything, and sends it for approval.</p>
        <a href="#p-ai" class="btn">Explore lesson notes</a>
      </div>
    </div>

    <!-- ===== Report cards ===== -->
    <div class="pn" role="tabpanel" id="tp-reports" aria-labelledby="tt-reports" style="--acc:#D4442F;--soft:#FDE2DC" hidden>
      <div class="stagebox">
        <div class="scene" data-ms="1250" data-hold="3200" role="img" aria-label="Animation: a teacher types test and exam scores, totals and grades appear by themselves, and a report card is published to parents.">
          <div class="app">
            <div class="bar"><b>Mathematics · JSS 2B</b><span class="chip">1st term</span></div>
            <div class="body">
              <div>
                <div class="grid6 h"><span>Student</span><span>CA1</span><span>CA2</span><span>Exam</span><span>Total</span><span>Grade</span></div>
                <div class="grid6 rw"><span class="nm">Adaeze O.</span><span data-at="1">18</span><span data-at="1">17</span><span data-at="1">52</span><b data-at="2">87</b><span data-at="2"><i class="p ok" style="font-style:normal">A1</i></span></div>
                <div class="grid6 rw"><span class="nm">Tunde B.</span><span data-at="2">14</span><span data-at="2">15</span><span data-at="2">41</span><b data-at="3">70</b><span data-at="3"><i class="p ok" style="font-style:normal">B2</i></span></div>
                <div class="grid6 rw"><span class="nm">Hadiza M.</span><span data-at="3">12</span><span data-at="3">16</span><span data-at="3">38</span><b data-at="4">66</b><span data-at="4"><i class="p part" style="font-style:normal">B3</i></span></div>
              </div>
              <div class="note" data-at="5"><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 6.5l2.4 2.4 4.6-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Totals, grades and positions worked out</div>
            </div>
            <div class="sheet" data-at="6">
              <div style="display:flex;justify-content:space-between;align-items:center"><b style="font-family:var(--display)">Report card · Adaeze Okafor</b></div>
              <div class="row2"><span>Mathematics</span><span>87 · A1</span></div>
              <div class="row2"><span>English Language</span><span>79 · A1</span></div>
              <div class="row2"><span>Basic Science</span><span>81 · A1</span></div>
              <div class="row2" style="border-top:.4cqw solid var(--line);padding-top:1.6cqw"><span>Position in class</span><span>1st of 42</span></div>
              <span class="p ok" data-at="7" style="justify-self:start">Published to parents</span>
            </div>
          </div>
        </div>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></div>
        <h3>Scores in, report cards out</h3>
        <p>Teachers type the scores. Totals, grades and class positions are worked out for you, then the report card goes to parents.</p>
        <a href="#p-ops" class="btn">Explore report cards</a>
      </div>
    </div>

    <!-- ===== Parents ===== -->
    <div class="pn" role="tabpanel" id="tp-parents" aria-labelledby="tt-parents" style="--acc:#0F8F80;--soft:#D9F3EE" hidden>
      <div class="stagebox">
        <div class="scene" data-ms="1300" data-hold="3200" role="img" aria-label="Animation: a parent opens the phone app, sees that results are ready, reads the results, then pays the school fees balance.">
          <div class="app">
            <div class="bar"><b>Parent portal</b><span class="chip">Sample school</span></div>
            <div class="screens">
              <div class="body scr" data-out="3">
                <div class="hello"><span class="avatar2">MO</span><div><b>Good morning, Mrs Okafor</b></div></div>
                <div class="kids"><span class="on2">Adaeze</span><span>Ifeanyi</span></div>
                <div class="tiles"><div class="tile"><small>Attendance</small><b>97%</b></div><div class="tile"><small>Fees to pay</small><b>₦125k</b></div></div>
                <div class="banner" data-at="1">Results are ready<i class="tap" data-at="2" data-out="3" style="right:4cqw;bottom:-3cqw"></i></div>
              </div>
              <div class="body scr" data-at="3" data-out="6">
                <b style="font-family:var(--display);font-size:5cqw">1st term results</b>
                <div>
                  <div class="li"><b>Mathematics</b><div><b>87</b> <span class="p ok">A1</span></div></div>
                  <div class="li"><b>English Language</b><div><b>79</b> <span class="p ok">A1</span></div></div>
                  <div class="li"><b>Basic Science</b><div><b>81</b> <span class="p ok">A1</span></div></div>
                </div>
                <div class="btnx" data-at="4">Download report card</div>
                <i class="tap" data-at="5" data-out="6" style="right:5cqw;bottom:-14cqw"></i>
              </div>
              <div class="body scr" data-at="6">
                <b style="font-family:var(--display);font-size:5cqw">Fees</b>
                <div class="tile"><small>Balance for 1st term</small><div class="slot"><b class="big" data-out="8">₦125,000</b><b class="big g" data-at="8">₦0</b></div></div>
                <div class="slot" style="min-height:21cqw">
                  <div style="display:grid;gap:2cqw" data-at="7" data-out="8"><div class="btnx">Pay with card<i class="tap" data-at="7" data-out="8"></i></div><small style="text-align:center">or bank transfer</small></div>
                  <div class="note" data-at="8"><svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 6.5l2.4 2.4 4.6-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Paid. Receipt saved.</div>
                </div>
              </div>
            </div>
            <div class="tnav"><span>Home<u data-out="3"></u></span><span>Results<u data-at="3" data-out="6"></u></span><span>Fees<u data-at="6"></u><i class="tap" data-at="5" data-out="6" style="bottom:-.5cqw;right:auto;left:50%;margin-left:-3cqw"></i></span></div>
          </div>
        </div>
        <button type="button" class="pausebtn" aria-label="Pause animation" aria-pressed="false"><svg class="i-pause" viewBox="0 0 18 18" aria-hidden="true"><path d="M5 2v14M13 2v14" stroke="currentColor" stroke-width="3.4"/></svg><svg class="i-play" viewBox="0 0 18 18" aria-hidden="true"><path d="M4 2l12 7-12 7z" fill="currentColor"/></svg></button>
      </div>
      <div class="pn-text">
        <div class="pn-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/></svg></div>
        <h3>Parents see it all on one phone</h3>
        <p>Results, fees and a pay button, right in the phone's browser. Nothing to install, and one login covers every child.</p>
        <a href="#p-portals" class="btn">Explore the parent portal</a>
      </div>
    </div>

    <!-- ===== CBT exams ===== -->
    <div class="pn" role="tabpanel" id="tp-cbt" aria-labelledby="tt-cbt" style="--acc:#5B4BD6;--soft:#E6E2FB" hidden>
      <div class="stagebox">
        <div class="scene" data-ms="1300" data-hold="3400" data-clock="860,12" role="img" aria-label="Animation: a student answers two multiple choice questions on a phone, hands in the test, and sees the score straight away.">
          <div class="app">
            <div class="bar"><b>Basic Science · JSS 1</b><span class="chip" data-clockface>14:20</span></div>
            <div class="screens">
              <div class="body" data-out="7">
                <div style="display:grid;gap:1.6cqw"><div style="display:flex;justify-content:space-between"><small>Question <span class="slot" style="display:inline-grid"><span data-out="4">1</span><span data-at="4">2</span></span> of 10</small><small data-at="3" data-out="4" style="color:#0B7568;font-weight:600">Answer saved</small></div><div class="prog"><i style="width:10%" data-out="4"></i><i style="width:20%;margin-top:-1.4cqw" data-at="4"></i></div></div>
                <div class="screens">
                  <div style="display:grid;gap:2.2cqw" data-out="4">
                    <div class="qtext">Which of these is a gas at room temperature?</div>
                    <div class="opt"><em>A</em><span>Water</span></div>
                    <div class="opt"><i class="pick" data-at="2"></i><em>B</em><span>Oxygen</span><i class="tap" data-at="1" data-out="2"></i></div>
                    <div class="opt"><em>C</em><span>Salt</span></div>
                    <div class="opt"><em>D</em><span>Wood</span></div>
                  </div>
                  <div style="display:grid;gap:2.2cqw" data-at="4" data-out="7">
                    <div class="qtext">What do we call water changing into steam?</div>
                    <div class="opt"><em>A</em><span>Melting</span></div>
                    <div class="opt"><em>B</em><span>Freezing</span></div>
                    <div class="opt"><i class="pick" data-at="5"></i><em>C</em><span>Evaporation</span><i class="tap" data-at="4" data-out="5" style="right:12cqw"></i></div>
                    <div class="opt"><em>D</em><span>Condensation</span></div>
                  </div>
                </div>
                <div class="btnx" data-at="6" data-out="7">Hand in<i class="tap" data-at="6" data-out="7"></i></div>
              </div>
              <div class="body result" data-at="7">
                <small>Handed in. Marked straight away.</small>
                <div class="ring"><span>9/10</span></div>
                <b class="big" style="font-size:6.4cqw">90% · Pass</b>
                <small>Objective questions are marked the moment you hand in. Theory answers wait for your teacher.</small>
              </div>
            </div>
          </div>
        </div>
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
  <p class="tour-note">Sample school, sample names and made-up amounts.</p>
  @endverbatim
</div>

@verbatim
<script>
(function(){
  var root=document.getElementById('tour');if(!root)return;
  var tabs=[].slice.call(root.querySelectorAll('[role=tab]')),
      panels=tabs.map(function(t){return document.getElementById(t.getAttribute('aria-controls'))}),
      buttons=[].slice.call(root.querySelectorAll('.pausebtn')),
      reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var playing=!reduce,visible=false,cur=0,n=0,timer=0,typer=0;

  function scene(i){return panels[i].querySelector('.scene')}
  function maxStep(sc){var m=0;[].forEach.call(sc.querySelectorAll('[data-at],[data-out]'),function(e){m=Math.max(m,+e.dataset.at||0,e.dataset.out?+e.dataset.out-1:0)});return m}
  function typeInto(e,text){
    clearInterval(typer);var k=0;e.classList.add('cur');
    typer=setInterval(function(){k++;e.textContent=text.slice(0,k);if(k>=text.length){clearInterval(typer);e.classList.remove('cur')}},24);
  }
  function set(sc,step,animate){
    [].forEach.call(sc.querySelectorAll('[data-at],[data-out]'),function(e){
      var at=e.dataset.at===undefined?0:+e.dataset.at,out=e.dataset.out===undefined?1e9:+e.dataset.out;
      e.classList.toggle('on',step>=at);e.classList.toggle('off',step>=out);
      if(e.dataset.type!==undefined){
        if(step<at){e.textContent='';e.classList.remove('cur')}
        else if(step===at&&animate){typeInto(e,e.dataset.type)}
        else{e.textContent=e.dataset.type;e.classList.remove('cur')}
      }
    });
    var ck=sc.dataset.clock;
    if(ck){var p=ck.split(','),s=Math.max(0,+p[0]-step*+p[1]),f=sc.querySelector('[data-clockface]');f.textContent=Math.floor(s/60)+':'+('0'+s%60).slice(-2)}
  }
  function stop(){clearTimeout(timer);clearInterval(typer)}
  function schedule(){
    stop();
    if(!playing||!visible||document.hidden)return;
    var sc=scene(cur),m=maxStep(sc),ms=+sc.dataset.ms||1300;
    timer=setTimeout(function(){
      if(n<m){n++;set(sc,n,true)}else{n=0;set(sc,0,false)}
      schedule();
    },n>=m?(+sc.dataset.hold||3000):(n===0?900:ms));
  }
  function show(i){
    stop();cur=i;n=0;
    tabs.forEach(function(t,k){var on=k===i;t.setAttribute('aria-selected',on);t.tabIndex=on?0:-1;panels[k].hidden=!on});
    var sc=scene(i);
    if(playing){set(sc,0,false)}else{n=maxStep(sc);set(sc,n,false)}
    schedule();
  }
  function setPlaying(p){
    playing=p;buttons.forEach(function(b){b.setAttribute('aria-pressed',!p);b.setAttribute('aria-label',p?'Pause animation':'Play animation')});
    if(p){var sc=scene(cur);if(n>=maxStep(sc)){n=0;set(sc,0,false)}}
    schedule();
  }
  tabs.forEach(function(t,i){
    t.addEventListener('click',function(){show(i)});
    t.addEventListener('keydown',function(e){
      var k=e.key==='ArrowRight'?1:e.key==='ArrowLeft'?-1:0;if(!k)return;
      e.preventDefault();var j=(i+k+tabs.length)%tabs.length;show(j);tabs[j].focus();
    });
  });
  buttons.forEach(function(b){b.addEventListener('click',function(){setPlaying(!playing)})});
  buttons.forEach(function(b){b.setAttribute('aria-pressed',!playing);b.setAttribute('aria-label',playing?'Pause animation':'Play animation')});
  document.addEventListener('visibilitychange',schedule);
  if('IntersectionObserver' in window){
    new IntersectionObserver(function(es){visible=es[0].isIntersecting;schedule()},{threshold:.25}).observe(root.querySelector('.tourcard'));
  }else{visible=true}
  show(0);
})();
</script>
@endverbatim
