<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>SchoolRuns | School management software</title>
<meta name="description" content="SchoolRuns brings fees, results, attendance, admissions and AI-assisted teaching into one secure platform for serious schools.">
<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Inter:wght@400;500;600;700&display=swap">
@verbatim
<style>
/* Layout follows Odoo's website-builder page: pale grey hero with a hand-lettered headline, big framed product screenshots with a soft glow, numbered hand-drawn steps, a squiggle divider. Screenshots are real screens from the SchoolRuns demo school (public/images/landing). */
:root{
  color-scheme: light;
  --ink:#0C1738;
  --ink-line:#26345E;
  --cobalt:#3346E0;
  --cobalt-dark:#2634B8;
  --sky:#1FA7F2;
  --coral:#F26B6B;
  --amber:#F5A623;
  --tide:#14B8A6;
  --label:#93A6FF;
  --paper:#FFFFFF;
  --grey:#F2F4F8;
  --line:#E4E8F2;
  --text:#1B2340;
  --muted:#4F5873;
  --script:"Caveat","Segoe Print","Comic Sans MS",cursive;
  --body:"Inter","Segoe UI",system-ui,sans-serif;
  --gutter:20px;
  --max:1160px;
  --hdr:68px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
[hidden]{display:none!important}
img{max-width:100%;display:block}
body{margin:0;background:var(--paper);color:var(--text);font-family:var(--body);font-size:17px;line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:inherit;text-decoration:none}
h1,h2,h3{margin:0;font-weight:700}
p{margin:0}
.wrap{max-width:var(--max);margin:0 auto;padding-inline:var(--gutter)}
:focus-visible{outline:2px solid var(--cobalt);outline-offset:3px;border-radius:6px}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}html{scroll-behavior:auto}}

/* ---------- Header ---------- */
.hdr{position:sticky;top:0;z-index:40;padding-top:env(safe-area-inset-top,0px);background:#fff;border-bottom:1px solid var(--line)}
.hdr .wrap{display:flex;align-items:center;justify-content:space-between;height:var(--hdr);gap:16px}
.logo{display:flex;align-items:center;gap:10px;font-weight:700;font-size:25px;letter-spacing:-.02em;color:var(--ink)}
.logo b span{color:var(--cobalt)}
.nav{display:none;align-items:center;gap:30px;font-size:15px;font-weight:500;color:var(--muted)}
.nav a:hover{color:var(--cobalt)}
.hdr-cta{display:none}
.burger{width:44px;height:44px;display:grid;place-items:center;background:none;border:0;color:var(--ink);cursor:pointer;padding:0;margin-right:-8px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;border-radius:8px;font-family:var(--body);font-weight:600;font-size:20px;line-height:1.2;padding:0 32px;min-height:62px;border:1.5px solid transparent;cursor:pointer;transition:background .15s, color .15s, box-shadow .15s;text-align:center}
.btn-solid{background:var(--cobalt);color:#fff;box-shadow:0 1px 0 rgba(255,255,255,.25) inset}
.btn-solid:hover{background:var(--cobalt-dark)}
.btn-ghost{background:#fff;border-color:#B9C0D6;color:var(--cobalt)}
.btn-ghost:hover{background:#F4F6FF}
.btn-white{background:#fff;color:var(--ink)}
.btn-white:hover{background:#EEF1FF}
.btn-line{border-color:rgba(255,255,255,.5);color:#fff}
.btn-line:hover{background:rgba(255,255,255,.08)}
.btn-sm{min-height:42px;padding:0 20px;font-size:15px}

/* ---------- Mobile menu ---------- */
.menu{position:fixed;inset:0;z-index:60;background:var(--ink);color:#fff;overflow-y:auto;padding-top:env(safe-area-inset-top,0px);padding-bottom:calc(32px + env(safe-area-inset-bottom,0px))}
.menu .top{display:flex;justify-content:space-between;align-items:center;height:var(--hdr)}
.menu .logo{color:#fff}
.menu .burger{color:#fff}
.menu ul{list-style:none;margin:12px 0 0;padding:0}
.m-item{font-weight:600;font-size:26px;letter-spacing:-.01em;display:flex;justify-content:space-between;align-items:center;width:100%;background:none;border:0;color:#fff;padding:16px 0;cursor:pointer;text-align:left;font-family:var(--body)}
.m-item svg{transition:transform .2s}
.m-item[aria-expanded="true"]{border-bottom:1px solid var(--ink-line)}
.m-item[aria-expanded="true"] svg{transform:rotate(180deg)}
.m-sub{padding:20px 0 22px;border-bottom:1px solid var(--ink-line);margin-bottom:8px}
.m-group+.m-group{margin-top:28px}
.m-label{font-size:14px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:var(--label);margin-bottom:8px}
.m-sub a{display:block;font-size:19px;padding:9px 0;color:#fff}
.menu .btn{margin-top:36px;width:100%}

/* ---------- Script headings ---------- */
.script{font-family:var(--script);font-weight:700;letter-spacing:.005em;line-height:1.08}
.blue{color:var(--sky)}
.coral{color:var(--coral)}
.plum{color:var(--cobalt)}
.hl{position:relative;display:inline-block;z-index:0}
.hl::before{content:"";position:absolute;left:-.12em;right:-.12em;top:.32em;bottom:.02em;background:var(--coral);opacity:.85;border-radius:3px;transform:rotate(-1.2deg);z-index:-1}
.under{position:relative;display:inline-block;white-space:nowrap}
.under svg{position:absolute;left:0;right:0;bottom:-.2em;width:100%;height:.3em}
.lead{font-size:clamp(20px,5.2vw,26px);line-height:1.6;color:var(--muted)}
.lead b{color:var(--ink);font-weight:700}

/* ---------- Hero ---------- */
.hero{background:var(--grey);padding-block:44px 0;position:relative;overflow:hidden}
.hero h1{font-family:var(--script);font-weight:700;font-size:clamp(50px,13.5vw,104px);line-height:1.05;letter-spacing:.005em;color:var(--ink)}
.hero .lead{margin-top:34px;max-width:30em}
.hero-ctas{display:grid;gap:16px;margin-top:44px;justify-items:start}
.hero-note{margin-top:26px;font-size:18px;color:var(--muted)}
.hero-note a{color:var(--cobalt);font-weight:600}
.hero-note a:hover{text-decoration:underline}
.stage{margin-top:64px;position:relative}
.stage::before{content:"";position:absolute;left:-30vw;right:-30vw;top:90px;bottom:0;background:#fff;border-radius:50% 50% 0 0/90px 90px 0 0}

/* ---------- Framed screenshots ---------- */
.frame{position:relative;background:#fff;border-radius:22px;padding:clamp(12px,3.2vw,26px);box-shadow:0 0 0 1px rgba(51,70,224,.06),0 24px 70px -18px rgba(31,167,242,.35),0 10px 30px -14px rgba(12,23,56,.18)}
.frame img{width:100%;height:auto;border-radius:6px;border:1px solid var(--line)}
.frame.tall{overflow:hidden}
.hero .frame{border-radius:22px 22px 0 0;padding-bottom:0}
.hero .frame img{border-bottom:0;border-radius:6px 6px 0 0}

/* ---------- Tour ---------- */
.tour{background:#fff;padding-block:56px 20px}
.tour .wrap{display:flex;align-items:center;gap:22px;justify-content:center}
.play{flex:none;width:112px;height:112px;border-radius:50%;background:#fff;display:grid;place-items:center;box-shadow:0 0 0 18px #F7F8FB,0 0 0 19px #EEF0F6,0 14px 40px -10px rgba(12,23,56,.25);transition:transform .2s}
.play:hover{transform:scale(1.05)}
.tour .script{font-size:clamp(34px,8vw,46px);color:var(--ink)}
.tour .script span{display:block;color:var(--cobalt)}

/* ---------- Showcase ---------- */
.show{padding-block:84px 24px;background:#fff}
.show .intro{text-align:left;max-width:760px}
.show h2{font-size:clamp(44px,11vw,76px)}
.show .lead{margin-top:30px}
.shots{display:grid;gap:34px;margin-top:56px}
.shot figcaption{padding:20px 6px 0}
.shot h3{font-size:22px;letter-spacing:-.01em}
.shot p{color:var(--muted);font-size:17px;margin-top:6px}
.shot.big .frame{padding-bottom:clamp(12px,3.2vw,26px)}

/* ---------- Four steps ---------- */
.easy{padding-block:96px 40px;background:#fff;text-align:center}
.easy h2{font-size:clamp(46px,12vw,84px)}
.easy .sub{font-family:var(--script);font-weight:700;font-size:clamp(34px,8vw,52px);color:var(--coral);transform:rotate(-2deg);margin-top:6px}
.easy .lead{margin:30px auto 0;max-width:34em}
.step{margin-top:78px;text-align:left}
.step-top{display:grid;grid-template-columns:44px 96px 1fr;align-items:center;gap:14px}
.step-top .no{width:44px;height:44px}
.step-top h3{font-family:var(--script);font-size:clamp(32px,8.4vw,48px);line-height:1.1;font-weight:700;color:var(--ink)}
.step p.cap{text-align:center;margin:30px auto 0;max-width:30em;color:var(--muted);font-size:19px}
.step p.cap b{color:var(--ink)}
.step .art{margin-top:34px}
.mini{background:#fff;border-radius:18px;box-shadow:0 0 0 1px rgba(51,70,224,.06),0 24px 60px -18px rgba(31,167,242,.3);padding:22px}
.dots{background-image:radial-gradient(#C9CFE0 1.4px,transparent 1.6px);background-size:22px 22px;border-radius:18px;padding:22px}

/* step 1: a form */
.formmini label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin:14px 0 6px}
.formmini label:first-child{margin-top:0}
.field{border:1px solid #D5DAE8;border-radius:8px;padding:11px 14px;font-size:16px;background:#fff;color:var(--ink);font-weight:500}
.field.ph{color:#98A0B8;font-weight:400}
.chips{display:flex;flex-wrap:wrap;gap:8px}
.chips span{border:1px solid #D5DAE8;border-radius:999px;padding:7px 14px;font-size:14px;font-weight:600;color:var(--muted)}
.chips span.on{background:var(--cobalt);border-color:var(--cobalt);color:#fff}
.formmini .go{margin-top:20px;background:var(--cobalt);color:#fff;border-radius:8px;text-align:center;padding:13px;font-weight:600}
.addr-pill{display:flex;align-items:center;justify-content:space-between;gap:10px}
.addr-pill small{font-size:13px;color:var(--tide);font-weight:600;white-space:nowrap}

/* step 3 and 4 phone row */
.phones{display:flex;justify-content:center;gap:16px;align-items:flex-end}
.phones figure{margin:0;flex:1;max-width:210px}
.phones figure:nth-child(2){transform:translateY(-22px)}
.ph{background:#0E1530;border-radius:26px;padding:6px;box-shadow:0 24px 60px -16px rgba(12,23,56,.45)}
.ph img{border-radius:20px;width:100%;height:auto}
.roles-mini{display:grid;gap:10px;grid-template-columns:1fr 1fr}
.roles-mini div{background:#fff;border:1px solid #DCE1EF;border-radius:12px;padding:14px 16px;box-shadow:0 8px 20px -14px rgba(12,23,56,.3)}
.roles-mini b{display:block;font-size:16px;color:var(--ink)}
.roles-mini span{font-size:13px;color:var(--muted);line-height:1.4;display:block;margin-top:2px}
.roles-mini div:nth-child(1){border-left:5px solid var(--cobalt)}
.roles-mini div:nth-child(2){border-left:5px solid var(--amber)}
.roles-mini div:nth-child(3){border-left:5px solid var(--coral)}
.roles-mini div:nth-child(4){border-left:5px solid var(--tide)}

/* ---------- Phones band ---------- */
.mobile{padding-block:80px 40px;background:var(--grey);margin-top:72px}
.mobile h2{font-size:clamp(42px,10.5vw,70px)}
.mobile .lead{margin-top:26px;max-width:32em}
.mobile .phones{margin-top:52px;gap:clamp(12px,3vw,40px)}
.mobile .phones figure{max-width:250px}

/* ---------- Trusted by / everyone ---------- */
.trust{padding-block:92px 30px;text-align:center;background:#fff}
.trust h2{font-size:clamp(38px,9.4vw,60px);color:var(--cobalt)}
.squig{display:block;width:100%;height:46px;margin-top:22px}
.kinds{list-style:none;margin:40px auto 0;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:0;border-top:1px solid var(--line);max-width:760px;text-align:left}
.kinds li{padding:16px 4px;border-bottom:1px solid var(--line);font-weight:600;font-size:19px;color:var(--ink)}
.kinds li:nth-child(odd){padding-right:14px}
.trust .more-shots{display:grid;gap:34px;margin-top:64px;text-align:left}

/* ---------- CTA ---------- */
.reach{padding-block:70px 80px;background:#fff}
.reach-card{border-radius:24px;color:#fff;padding:56px 24px;text-align:center;background:radial-gradient(70% 60% at 0% 100%,rgba(242,107,107,.35),transparent 70%),linear-gradient(160deg,#2A2F9E,#3346E0 60%,#4A5CF0)}
.reach-card h2{font-family:var(--script);font-size:clamp(46px,12vw,80px);line-height:1.05}
.reach-card p{color:rgba(255,255,255,.88);font-size:19px;max-width:30em;margin:18px auto 0}
.reach-card .ctas{display:grid;gap:14px;margin:34px auto 0;max-width:420px}
.contact-lines{display:grid;gap:4px;margin-top:30px;font-size:16px;color:rgba(255,255,255,.82)}
.contact-lines b{color:#fff;font-weight:600}
.note{display:block;font-size:13px;color:rgba(255,255,255,.58);margin-top:6px}

/* ---------- Footer ---------- */
footer{background:var(--ink);color:#C2CBE4;padding-block:64px calc(96px + env(safe-area-inset-bottom,0px))}
.f-grid{display:grid;gap:44px}
.f-col h4{margin:0 0 14px;font-size:18px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;color:#fff}
.f-col h5{margin:22px 0 6px;font-size:14px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:var(--label)}
.f-col a{display:block;padding:8px 0;font-size:17px}
.f-col a:hover{color:#fff}
.addr{font-size:16px;line-height:1.6}
.addr+.addr{margin-top:16px}
.addr b{color:#fff;display:block;font-weight:600}
.addr i{font-style:normal;color:#7F8BB0;font-size:14px}
.social{display:flex;gap:10px;margin-top:4px}
.social a{width:46px;height:46px;border-radius:50%;border:1px solid var(--ink-line);display:grid;place-items:center;color:#fff;padding:0}
.social a:hover{background:#18264F}
.f-base{border-top:1px solid var(--ink-line);margin-top:48px;padding-top:24px;font-size:14px;color:#8590B4}

/* ---------- Chat button ---------- */
.fab{position:fixed;right:16px;bottom:calc(16px + env(safe-area-inset-bottom,0px));z-index:30;width:54px;height:54px;border-radius:18px 18px 4px 18px;background:var(--cobalt);color:#fff;display:grid;place-items:center;border:0;cursor:pointer;box-shadow:0 10px 24px -8px rgba(12,23,56,.5)}
.chat{position:fixed;right:16px;bottom:calc(80px + env(safe-area-inset-bottom,0px));z-index:31;width:min(340px,calc(100vw - 32px));background:#fff;border-radius:18px;box-shadow:0 24px 60px -18px rgba(12,23,56,.5);overflow:hidden}
.chat-h{background:var(--cobalt);color:#fff;padding:16px 18px}
.chat-h b{font-size:16px}
.chat-h p{font-size:13px;color:#DDE3FF}
.chat-b{padding:10px 18px 14px}
.chat-b a{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line);font-weight:600;font-size:14px}
.chat-b a:last-child{border-bottom:0}

/* On phones the wide screenshots are cropped to their main area (no side menu) so the text stays readable */
@media (max-width:899px){
  .frame>img{aspect-ratio:1.12;object-fit:cover;object-position:62% 0}
  .hero .frame>img{aspect-ratio:1.0;object-position:52% 0}
  .phone-float{display:block;position:absolute;right:6px;bottom:-1px;width:112px;z-index:3}
  .phone-float .ph{border-radius:20px;padding:4px}
  .phone-float .ph img{border-radius:16px}
}

/* ---------- Desktop ---------- */
@media (min-width:900px){
  :root{--gutter:32px;--hdr:76px}
  body{font-size:18px}
  .nav,.hdr-cta{display:flex}
  .hdr-cta{gap:10px;align-items:center}
  .burger{display:none}
  .hero{padding-top:72px}
  .hero .wrap{display:grid;grid-template-columns:1fr;}
  .hero-ctas{grid-auto-flow:column;justify-content:start;gap:18px}
  .hero h1{max-width:9.5em}
  .hero .lead{max-width:34em;font-size:26px}
  .stage{margin-top:80px}
  .phone-float{display:block;position:absolute;right:-8px;bottom:-1px;width:190px;z-index:3}
  .phone-float .ph{border-radius:32px;padding:7px}
  .tour{padding-block:80px 30px}
  .shots{grid-template-columns:1fr 1fr;gap:56px 40px}
  .shot.big{grid-column:1 / -1}
  .step{margin-top:110px}
  .step-top{grid-template-columns:52px 120px auto;justify-content:center;gap:26px}
  .step-top .no{width:52px;height:52px}
  .step .art{max-width:880px;margin-inline:auto}
  .phones figure{max-width:230px}
  .mobile .wrap{display:grid;grid-template-columns:1fr 1.15fr;gap:60px;align-items:center}
  .mobile .phones{margin-top:0}
  .kinds{grid-template-columns:repeat(3,1fr)}
  .kinds li:nth-child(odd){padding-right:4px}
  .trust .more-shots{grid-template-columns:repeat(3,1fr);gap:40px 28px}
  .reach-card{padding:90px 48px}
  .reach-card .ctas{grid-auto-flow:column;max-width:none;justify-content:center}
  .f-grid{grid-template-columns:1.2fr 1fr 1fr 1.2fr}
  footer{padding-bottom:48px}
}
</style>
@endverbatim
</head>
<body>
<!-- ============ HEADER ============ -->
<header class="hdr" id="hdr">
  <div class="wrap">
    <a href="#top" class="logo" aria-label="SchoolRuns home">
      <svg width="30" height="30" viewBox="0 0 30 30" aria-hidden="true"><rect width="30" height="30" rx="7" fill="#3346E0"/><path d="M9 19.6c.8 1.9 3.2 2.9 6.2 2.9 3.6 0 5.8-1.5 5.8-3.6 0-4.9-11.6-2.7-11.6-7.6 0-2 2.2-3.3 5.4-3.3 2.6 0 4.6.9 5.4 2.6" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/></svg>
      <b>School<span>Runs</span></b>
    </a>
    <nav class="nav" aria-label="Main">
      <a href="#features">What it does</a>
      <a href="#how">How it works</a>
      <a href="#schools">Who it's for</a>
      <a href="#contact">Contact</a>
    </nav>
    <div class="hdr-cta">
      <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Log in</a>
      <a href="{{ route('register') }}" class="btn btn-solid btn-sm">Register your school</a>
    </div>
    <button class="burger" id="openMenu" aria-label="Open menu" aria-controls="menu" aria-expanded="false">
      <svg width="28" height="20" viewBox="0 0 28 20" aria-hidden="true"><path d="M0 2h28M0 10h28M0 18h28" stroke="currentColor" stroke-width="3"/></svg>
    </button>
  </div>
</header>

@include('landing-menu')

<main id="top">

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="wrap">
    <h1>Run your school<br><span class="blue">without the paperwork</span></h1>
    <p class="lead"><b>One platform for the whole school, not a pile of spreadsheets.</b> Collect fees, publish results, take attendance, handle admissions and write lesson notes with AI, with a login for every parent, student and teacher.</p>
    <div class="hero-ctas">
      <a href="{{ route('register') }}" class="btn btn-solid">Register your school</a>
      @if (config('app.demo_enabled'))
      <a href="{{ route('demo') }}" class="btn btn-ghost">Try the demo</a>
      @else
      <a href="#contact" class="btn btn-ghost">Book a demo</a>
      @endif
    </div>
    @if (config('app.demo_enabled'))
    <p class="hero-note">A full demo school with sample students, fees and results. No sign-up needed.</p>
    @endif

    <div class="stage">
      <div class="frame">
        <img src="{{ asset('images/landing/fees.jpg') }}" width="1500" height="926" alt="The SchoolRuns invoices screen: money still owed, money collected, overdue parts and a list of every student's invoices" fetchpriority="high">
      </div>
      <div class="phone-float" aria-hidden="true"><div class="ph"><img src="{{ asset('images/landing/parent-fees.jpg') }}" width="585" height="1170" alt="" loading="lazy"></div></div>
    </div>
  </div>
</section>

<!-- ============ TOUR ============ -->
<section class="tour">
  <div class="wrap">
    @if (config('app.demo_enabled'))
    <a href="{{ route('demo') }}" class="play" aria-label="Open the live demo">
    @else
    <a href="#contact" class="play" aria-label="Book a demo">
    @endif
      <svg width="64" height="64" viewBox="0 0 64 64" aria-hidden="true"><path d="M18 11c-1 0-1 1-1 2v38c0 1 1 2 2 1l34-19c1-1 1-2 0-3L19 11z" fill="#F26B6B" transform="translate(5 4)"/><path d="M14 8v46L52 31 14 8z" fill="none" stroke="#0C1738" stroke-width="5" stroke-linejoin="round"/></svg>
    </a>
    <div class="script">SchoolRuns<span>Live demo</span></div>
  </div>
</section>

<!-- ============ SHOWCASE ============ -->
<section class="show" id="features">
  <div class="wrap">
    <div class="intro">
      <h2 class="script">Built to run the <span class="under">whole school<svg viewBox="0 0 300 18" preserveAspectRatio="none" aria-hidden="true"><path d="M3 11c30-9 52 6 82-1s55-8 85 0 58 5 85-2 36-3 42 0" fill="none" stroke="#14B8A6" stroke-width="5" stroke-linecap="round"/></svg></span></h2>
      <p class="lead"><b>Don't just collect fees, run the school.</b> Every screen below is a real SchoolRuns screen from our demo school. Money, marks, teaching and admissions all share one student record, so nothing is typed twice.</p>
    </div>

    <div class="shots">
      <figure class="shot big">
        <div class="frame"><img src="{{ asset('images/landing/fees.jpg') }}" width="1500" height="926" alt="Invoices with what is owed, collected and overdue" loading="lazy"></div>
        <figcaption>
          <h3>Fees that collect themselves</h3>
          <p>Raise invoices for a whole class at once, let parents pay online through the school's own payment account, and watch the ledger match every naira to the right student. Instalments, scholarships and overdue parts are built in.</p>
        </figcaption>
      </figure>
      <figure class="shot">
        <div class="frame"><img src="{{ asset('images/landing/questions.jpg') }}" width="1500" height="927" alt="A question bank for JSS Mathematics with objective and theory questions and a marking guide" loading="lazy"></div>
        <figcaption>
          <h3>Exam questions, written in minutes</h3>
          <p>Build question banks by subject and topic, or let AI draft objective and theory questions with marking guides. Students sit the test on screen and objective answers are marked at once.</p>
        </figcaption>
      </figure>
      <figure class="shot">
        <div class="frame"><img src="{{ asset('images/landing/lesson-notes.jpg') }}" width="1500" height="927" alt="Lesson notes in draft, waiting for approval, approved and sent back" loading="lazy"></div>
        <figcaption>
          <h3>Lesson notes that follow the scheme of work</h3>
          <p>Upload the scheme, pick the week and let AI draft the note in your school's own format. The head approves or sends it back with a comment before anything is used.</p>
        </figcaption>
      </figure>
    </div>
  </div>
</section>

<!-- ============ FOUR STEPS ============ -->
<section class="easy" id="how">
  <div class="wrap">
    <h2 class="script">Your school online,<br><span class="hl">ready</span> soon</h2>
    <p class="sub">easy as 1, 2, 3, 4!</p>
    <p class="lead"><b>No technical skills? No problem.</b> Register your school, set up the session, invite your people and run the term. Our team helps you bring your records across.</p>

    <!-- 1 -->
    <div class="step">
      <div class="step-top">
        <svg class="no" viewBox="0 0 44 44" aria-hidden="true"><path d="M22 4C11 3.5 3.5 11 4 22c.5 11 8 18 19 17.5C34 39 40.5 32 40 21 39.5 11 32 4 22 4z" fill="none" stroke="#F26B6B" stroke-width="2.6" stroke-linecap="round"/><text x="22" y="30" text-anchor="middle" font-family="Caveat,cursive" font-weight="700" font-size="26" fill="#F26B6B">1</text></svg>
        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true"><path d="M30 22h28l16 16v40H30z" fill="#F26B6B" transform="translate(7 7)"/><path d="M26 16h30l16 16v44H26z" fill="#fff" stroke="#0C1738" stroke-width="5" stroke-linejoin="round"/><path d="M56 16v16h16" fill="none" stroke="#0C1738" stroke-width="5" stroke-linejoin="round"/><path d="M49 42v22M38 53h22" stroke="#0C1738" stroke-width="5" stroke-linecap="round"/></svg>
        <h3>Register your school</h3>
      </div>
      <div class="art">
        <div class="dots"><div class="mini formmini">
          <label>School name</label><div class="field">Greenfield College</div>
          <label>Levels taught</label><div class="chips"><span class="on">Primary</span><span class="on">Junior secondary</span><span class="on">Senior secondary</span><span>Nursery</span></div>
          <label>Your school's web address</label><div class="field addr-pill"><span>greenfield-college</span><small>Free to use</small></div>
          <div class="go">Create my school</div>
        </div></div>
      </div>
      <p class="cap">Enter your school's name and who runs it. <b>Your private school account is created straight away.</b></p>
    </div>

    <!-- 2 -->
    <div class="step">
      <div class="step-top">
        <svg class="no" viewBox="0 0 44 44" aria-hidden="true"><path d="M22 4C11 3.5 3.5 11 4 22c.5 11 8 18 19 17.5C34 39 40.5 32 40 21 39.5 11 32 4 22 4z" fill="none" stroke="#F5A623" stroke-width="2.6" stroke-linecap="round"/><text x="22" y="30" text-anchor="middle" font-family="Caveat,cursive" font-weight="700" font-size="26" fill="#F5A623">2</text></svg>
        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true"><rect x="22" y="26" width="58" height="56" rx="10" fill="#F5A623"/><rect x="14" y="18" width="58" height="56" rx="10" fill="#fff" stroke="#0C1738" stroke-width="5"/><path d="M14 36h58M30 10v16M56 10v16" stroke="#0C1738" stroke-width="5" stroke-linecap="round"/><path d="M28 50h10M44 50h10M28 62h10" stroke="#0C1738" stroke-width="5" stroke-linecap="round"/></svg>
        <h3>Set up your session</h3>
      </div>
      <div class="art"><div class="frame"><img src="{{ asset('images/landing/payment-plans.jpg') }}" width="1500" height="927" alt="Payment plans that split a term's fee into dated parts" loading="lazy"></div></div>
      <p class="cap">Add classes, subjects, fee items and grading, or copy a Nigerian, British, American or Montessori setup. <b>Import students from a spreadsheet</b> and choose how parents may pay, in full or in parts.</p>
    </div>

    <!-- 3 -->
    <div class="step">
      <div class="step-top">
        <svg class="no" viewBox="0 0 44 44" aria-hidden="true"><path d="M22 4C11 3.5 3.5 11 4 22c.5 11 8 18 19 17.5C34 39 40.5 32 40 21 39.5 11 32 4 22 4z" fill="none" stroke="#14B8A6" stroke-width="2.6" stroke-linecap="round"/><text x="22" y="30" text-anchor="middle" font-family="Caveat,cursive" font-weight="700" font-size="26" fill="#14B8A6">3</text></svg>
        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true"><path d="M16 30h66v46H16z" fill="#14B8A6" transform="translate(7 6)"/><rect x="10" y="24" width="66" height="46" rx="8" fill="#fff" stroke="#0C1738" stroke-width="5"/><path d="M12 28l31 24 31-24" fill="none" stroke="#0C1738" stroke-width="5" stroke-linejoin="round" stroke-linecap="round"/></svg>
        <h3>Invite your people</h3>
      </div>
      <div class="art"><div class="dots"><div class="roles-mini">
        <div><b>School admin</b><span>Students, fees, staff and settings</span></div>
        <div><b>Teachers</b><span>Registers, scores, tests and lesson notes</span></div>
        <div><b>Parents</b><span>Results, fees and attendance on a phone</span></div>
        <div><b>Students</b><span>Homework, timetable and online tests</span></div>
      </div></div></div>
      <p class="cap">Send logins to staff, parents and students. <b>Each person sees only what they should</b>, and one parent login covers all their children.</p>
    </div>

    <!-- 4 -->
    <div class="step">
      <div class="step-top">
        <svg class="no" viewBox="0 0 44 44" aria-hidden="true"><path d="M22 4C11 3.5 3.5 11 4 22c.5 11 8 18 19 17.5C34 39 40.5 32 40 21 39.5 11 32 4 22 4z" fill="none" stroke="#3346E0" stroke-width="2.6" stroke-linecap="round"/><text x="22" y="30" text-anchor="middle" font-family="Caveat,cursive" font-weight="700" font-size="26" fill="#3346E0">4</text></svg>
        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true"><path d="M20 38l34-16 34 16-34 16z" fill="#3346E0" transform="translate(6 8)"/><path d="M14 38l34-16 34 16-34 16z" fill="#fff" stroke="#0C1738" stroke-width="5" stroke-linejoin="round"/><path d="M30 50v16c0 6 36 6 36 0V50" fill="none" stroke="#0C1738" stroke-width="5" stroke-linejoin="round"/><path d="M82 40v22" stroke="#0C1738" stroke-width="5" stroke-linecap="round"/></svg>
        <h3>Run the term</h3>
      </div>
      <div class="art"><div class="phones">
        <figure><div class="ph"><img src="{{ asset('images/landing/parent-home.jpg') }}" width="585" height="1170" alt="A parent's home screen on a phone showing attendance and fees" loading="lazy"></div></figure>
        <figure><div class="ph"><img src="{{ asset('images/landing/parent-results.jpg') }}" width="585" height="1170" alt="A child's term results on a phone" loading="lazy"></div></figure>
        <figure><div class="ph"><img src="{{ asset('images/landing/parent-fees.jpg') }}" width="585" height="1170" alt="A parent's fee status on a phone" loading="lazy"></div></figure>
      </div></div>
      <p class="cap">Teachers enter scores and report cards are made for you. <b>Parents see results and fees on their phone</b> and pay online, with no queue at the bursar's office.</p>
    </div>
  </div>
</section>

<!-- ============ EVERY KIND OF SCHOOL ============ -->
<section class="trust" id="schools">
  <div class="wrap">
    <h2 class="script">Made for every kind of school</h2>
    <svg class="squig" viewBox="0 0 1000 46" preserveAspectRatio="none" aria-hidden="true"><path d="M4 30C4 10 40 12 120 22S250 34 400 22 470 4 500 4s30 0 100 18 200 6 280 0 116-8 116 12" fill="none" stroke="#3346E0" stroke-width="5" stroke-linecap="round"/></svg>
    <ul class="kinds">
      <li>Nursery</li><li>Primary</li><li>Junior secondary</li><li>Senior secondary</li><li>Boarding schools</li><li>Groups of schools</li>
    </ul>

    <div class="more-shots">
      <figure class="shot">
        <div class="frame"><img src="{{ asset('images/landing/admissions.jpg') }}" width="1500" height="927" alt="Admission inquiries moving from new to follow-up to exam booked" loading="lazy"></div>
        <figcaption><h3>Admissions, from first call to first day</h3><p>Track every inquiry, book entrance exams, offer a place and enrol the child in one click.</p></figcaption>
      </figure>
      <figure class="shot">
        <div class="frame"><img src="{{ asset('images/landing/id-cards.jpg') }}" width="1500" height="927" alt="Student ID cards with photo and card status" loading="lazy"></div>
        <figcaption><h3>ID cards with a QR code</h3><p>Print cards for the whole school and scan them at the gate to check a student, or mark them present.</p></figcaption>
      </figure>
      <figure class="shot">
        <div class="frame"><img src="{{ asset('images/landing/houses.jpg') }}" width="1500" height="927" alt="House points leaderboard for four school houses" loading="lazy"></div>
        <figcaption><h3>Houses, clubs and points</h3><p>Share students fairly into houses and keep a live leaderboard for sports day and every day.</p></figcaption>
      </figure>
    </div>
  </div>
</section>

<!-- ============ REACH OUT ============ -->
<section class="reach" id="register">
  <div class="wrap">
    <div class="reach-card" id="contact">
      <h2>Dream it today, go live in days</h2>
      <p>Register your school in minutes, or book a guided demo with our team first.</p>
      <div class="ctas">
        <a href="{{ route('register') }}" class="btn btn-white">Register your school</a>
        @if (config('app.demo_enabled'))
        <a href="{{ route('demo') }}" class="btn btn-line">Try the demo</a>
        @else
        <a href="#contact" class="btn btn-line">Book a demo</a>
        @endif
      </div>
      <div class="contact-lines">
        <span>hello@schoolruns.com</span>
        <span>+234 000 000 0000</span>
        <span class="note">Contact details are placeholders</span>
      </div>
    </div>
  </div>
</section>
</main>

@include('landing-footer')

<!-- ============ CHAT ============ -->
<div class="chat" id="chat" hidden role="dialog" aria-label="Chat with SchoolRuns">
  <div class="chat-h"><b>How can we help?</b><p>Our team replies during school hours.</p></div>
  <div class="chat-b">
    <a href="{{ route('register') }}">Register my school <span>→</span></a>
    <a href="#contact">Book a demo <span>→</span></a>
    <a href="#features">Ask about fees and payments <span>→</span></a>
  </div>
</div>
<button class="fab" id="fab" aria-label="Open chat" aria-controls="chat" aria-expanded="false">
  <svg width="24" height="24" viewBox="0 0 26 26" aria-hidden="true"><rect x="2" y="4" width="22" height="16" rx="6" fill="#fff"/><path d="M8 10.5h10M8 14h6" stroke="#3346E0" stroke-width="2" stroke-linecap="round"/></svg>
</button>
@verbatim
<script>
(function(){
  var menu=document.getElementById('menu'),openB=document.getElementById('openMenu'),closeB=document.getElementById('closeMenu');
  function setMenu(o){menu.hidden=!o;openB.setAttribute('aria-expanded',o);document.body.style.overflow=o?'hidden':'';if(o)closeB.focus();}
  openB.addEventListener('click',function(){setMenu(true)});
  closeB.addEventListener('click',function(){setMenu(false);openB.focus()});
  menu.querySelectorAll('[data-close]').forEach(function(a){a.addEventListener('click',function(){setMenu(false)})});
  var pt=document.getElementById('prodToggle'),ps=document.getElementById('prodSub');
  pt.addEventListener('click',function(){var o=pt.getAttribute('aria-expanded')!=='true';pt.setAttribute('aria-expanded',o);ps.hidden=!o});
  var chat=document.getElementById('chat'),fab=document.getElementById('fab');
  function toggleChat(o){chat.hidden=!o;fab.setAttribute('aria-expanded',o)}
  fab.addEventListener('click',function(){toggleChat(chat.hidden)});
  chat.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){toggleChat(false)})});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){if(!menu.hidden)setMenu(false);if(!chat.hidden)toggleChat(false)}});
})();
</script>
@endverbatim
</body>
</html>
