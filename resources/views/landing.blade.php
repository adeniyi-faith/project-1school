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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Figtree:wght@400;500;600&display=swap">
@verbatim
<style>
/* Layout: Helium-style marketing page. Header sits transparent on the hero and turns navy on scroll; hero with dotted globe; white card; navy statement; tinted numbered product cards; CTA card; navy footer. Single committed look, colours set explicitly. */
:root{
  color-scheme: light;
  --ink:#0C1738;
  --ink-line:#26345E;
  --hero-a:#2A2F9E;
  --hero-b:#4549D6;
  --hero-c:#5B63EA;
  --cobalt:#3346E0;
  --tide:#0F9F8F;
  --brass:#D99A2B;
  --coral:#E05A46;
  --label:#93A6FF;
  --paper:#FFFFFF;
  --mist:#F5F7FC;
  --line:#E4E8F2;
  --text:#0E1A3A;
  --muted:#5A6482;
  --display:"Plus Jakarta Sans","Helvetica Neue",Arial,sans-serif;
  --body:"Figtree","Segoe UI",system-ui,sans-serif;
  --mono:ui-monospace,"SF Mono",Menlo,Consolas,monospace;
  --gutter:20px;
  --max:1160px;
  --hdr:68px;
}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
[hidden]{display:none!important}
img{max-width:100%}
body{margin:0;background:var(--paper);color:var(--text);font-family:var(--body);font-size:16px;line-height:1.55;-webkit-font-smoothing:antialiased;overflow-x:hidden}
a{color:inherit;text-decoration:none}
h1,h2,h3{font-family:var(--display);text-wrap:balance;margin:0}
p{margin:0}
.wrap{max-width:var(--max);margin:0 auto;padding-inline:var(--gutter)}
:focus-visible{outline:2px solid var(--label);outline-offset:3px;border-radius:6px}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}html{scroll-behavior:auto}}

/* ---------- Header: transparent over hero, navy after scroll ---------- */
.hdr{position:fixed;left:0;right:0;top:0;z-index:40;padding-top:env(safe-area-inset-top,0px);background:transparent;color:#fff;transition:background .25s, box-shadow .25s}
.hdr.solid{background:var(--ink);box-shadow:0 1px 0 rgba(255,255,255,.06)}
.hdr .wrap{display:flex;align-items:center;justify-content:space-between;height:var(--hdr);gap:16px}
.logo{display:flex;align-items:center;gap:10px;font-family:var(--display);font-weight:700;font-size:21px;letter-spacing:-.01em;color:#fff}
.logo b{font-weight:700}
.logo b span{color:rgba(255,255,255,.62)}
.logo svg{flex:none}
.nav{display:none;align-items:center;gap:30px;font-size:15px;color:rgba(255,255,255,.82)}
.nav a:hover{color:#fff}
.hdr-cta{display:none}
.burger{width:44px;height:44px;display:grid;place-items:center;background:none;border:0;color:#fff;cursor:pointer;padding:0;margin-right:-8px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;border-radius:999px;font-family:var(--display);font-weight:600;font-size:16px;line-height:1;padding:0 26px;height:54px;border:1.5px solid transparent;cursor:pointer;white-space:nowrap;transition:background .15s, color .15s}
.btn-white{background:#fff;color:var(--ink)}
.btn-white:hover{background:#EEF1FF}
.btn-line{border-color:rgba(255,255,255,.5);color:#fff}
.btn-line:hover{background:rgba(255,255,255,.08)}
.btn-sm{height:42px;padding:0 20px;font-size:14px}
.btn-block{width:100%}

/* ---------- Mobile menu ---------- */
.menu{position:fixed;inset:0;z-index:60;background:var(--ink);color:#fff;overflow-y:auto;padding-top:env(safe-area-inset-top,0px);padding-bottom:calc(32px + env(safe-area-inset-bottom,0px))}
.menu .top{display:flex;justify-content:space-between;align-items:center;height:var(--hdr)}
.menu ul{list-style:none;margin:12px 0 0;padding:0}
.m-item{font-family:var(--display);font-weight:600;font-size:28px;letter-spacing:-.01em;display:flex;justify-content:space-between;align-items:center;width:100%;background:none;border:0;color:#fff;padding:16px 0;cursor:pointer;text-align:left}
.m-item svg{transition:transform .2s}
.m-item[aria-expanded="true"]{border-bottom:1px solid var(--ink-line)}
.m-item[aria-expanded="true"] svg{transform:rotate(180deg)}
.m-sub{padding:20px 0 22px;border-bottom:1px solid var(--ink-line);margin-bottom:8px}
.m-group+.m-group{margin-top:28px}
.m-label{font-size:14px;font-weight:500;letter-spacing:.04em;text-transform:uppercase;color:var(--label);margin-bottom:8px}
.m-sub a{display:block;font-size:19px;padding:9px 0;color:#fff}
.menu .btn-block{margin-top:36px}

/* ---------- Hero ---------- */
.hero{position:relative;overflow:hidden;color:#fff;background:
  radial-gradient(60% 40% at 0% 42%, rgba(196,92,160,.28), transparent 70%),
  linear-gradient(180deg,var(--hero-a) 0%,var(--hero-b) 55%,var(--hero-c) 100%)}
.hero canvas{position:absolute;left:50%;transform:translateX(-50%);width:160vw;max-width:none;height:160vw;bottom:-86vw;pointer-events:none}
.hero .wrap{position:relative;text-align:center;padding-top:calc(var(--hdr) + env(safe-area-inset-top,0px) + 64px);padding-bottom:150px}
.hero h1{font-size:clamp(34px,9.4vw,64px);line-height:1.18;font-weight:700;text-transform:uppercase;letter-spacing:0;max-width:16ch;margin:0 auto}
.hero p{font-size:17px;line-height:1.75;color:rgba(255,255,255,.88);max-width:34ch;margin:26px auto 0}
.hero-ctas{display:grid;gap:12px;margin:40px auto 0;max-width:400px}
.hero-ctas .btn svg{flex:none}
.hero .alt{display:inline-block;margin-top:18px;font-size:15px;color:#fff;border-bottom:1px solid rgba(255,255,255,.45);padding-bottom:2px}

/* ---------- White card ---------- */
.types{position:relative;z-index:2;margin-top:-72px}
.types-card{background:#fff;border-radius:20px;box-shadow:0 20px 50px -28px rgba(12,23,56,.45);padding:28px 24px}
.types-card h2{font-family:var(--body);font-size:15px;font-weight:500;color:var(--muted);text-align:center}
.types-list{list-style:none;margin:18px 0 0;padding:0;display:grid;grid-template-columns:1fr 1fr;column-gap:20px}
.types-list li{padding:13px 0;border-top:1px solid var(--line);font-family:var(--display);font-weight:600;font-size:16px}

/* ---------- Statement ---------- */
.statement{background:var(--ink);color:#fff;margin-top:72px;padding-block:72px 0;overflow:hidden}
.statement h2{font-size:clamp(25px,6.4vw,40px);line-height:1.35;font-weight:600;max-width:26ch}
.statement h2 span{color:rgba(255,255,255,.55)}
.statement p.lead{color:#B6C0DC;font-size:17px;max-width:50ch;margin-top:22px}
.stage{margin-top:48px}
.screen{background:#F7F9FD;border-radius:16px 16px 0 0;padding:14px;color:var(--text);max-width:900px;margin:0 auto}
.chrome{display:flex;gap:6px;align-items:center;margin-bottom:12px}
.chrome i{width:9px;height:9px;border-radius:50%;background:#D5DBEA}
.chrome span{margin-left:10px;font-family:var(--mono);font-size:11px;color:var(--muted);background:#fff;border:1px solid var(--line);border-radius:6px;padding:3px 10px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;min-width:0}
.dash{display:grid;grid-template-columns:1fr;gap:10px}
.dash-side{display:none}
.dash-main{display:grid;gap:10px;min-width:0}
.kpis{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.kpi{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px;min-width:0}
.kpi b{display:block;font-family:var(--display);font-size:19px;font-variant-numeric:tabular-nums;margin-top:2px}
.kpi span{font-size:12px;color:var(--muted)}
.kpi .bar{height:4px;border-radius:9px;background:var(--line);margin-top:8px;overflow:hidden}
.kpi .bar i{display:block;height:100%;border-radius:9px}
.dash-two{display:grid;gap:10px}
.panel{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px;min-width:0}
.panel h4{margin:0 0 8px;font-family:var(--display);font-size:13px;display:flex;justify-content:space-between;align-items:center}
.panel h4 span{font-family:var(--body);font-weight:500;color:var(--muted);font-size:11px}
.chartpanel{display:none}
.sample{font-size:11px;font-weight:500;color:var(--muted);font-style:italic}
.row{display:grid;grid-template-columns:1fr auto auto;gap:10px;align-items:center;padding:8px 0;border-top:1px solid var(--line);font-size:12px}
.row:first-of-type{border-top:0}
.amt{font-variant-numeric:tabular-nums;font-weight:600}
.pill{font-size:10.5px;font-weight:600;border-radius:999px;padding:2px 8px;white-space:nowrap}
.pill.ok{background:#DDF4F0;color:#0B7568}
.pill.part{background:#FBEED6;color:#8F610E}
.pill.due{background:#FCE3DE;color:#AE3726}

/* ---------- Product cards ---------- */
.pillars{padding-block:80px 32px}
.sec-head{max-width:640px}
.sec-head h2{font-size:clamp(28px,7vw,42px);line-height:1.2;font-weight:700;text-transform:uppercase}
.sec-head p{color:var(--muted);font-size:17px;margin-top:14px}
.cards{display:grid;gap:24px;margin-top:40px}
.card{border-radius:24px;padding:36px 24px 28px;display:flex;flex-direction:column;gap:22px;min-width:0}
.card .num{font-family:var(--display);font-weight:600;font-size:80px;line-height:1;letter-spacing:-.03em}
.card h3{font-size:clamp(23px,6vw,28px);line-height:1.25;font-weight:700}
.card p.desc{font-size:17px;line-height:1.7;color:#2A3555}
.card p.desc b{color:var(--text);font-weight:600}
.feat{list-style:none;margin:0;padding:0;border-top:1px solid rgba(14,26,58,.1)}
.feat li{padding:13px 0;border-bottom:1px solid rgba(14,26,58,.1);font-size:16px}
.more{display:flex;justify-content:center;align-items:center;gap:12px;height:54px;border-radius:999px;border:1.5px solid;font-family:var(--display);font-weight:600;font-size:15px;color:var(--text);white-space:nowrap;transition:background .15s}
.more:hover{background:rgba(255,255,255,.75)}
.c1{background:#F2F4FF}.c1 .num{color:var(--cobalt)}.c1 .more{border-color:var(--cobalt)}
.c2{background:#FFF8EC}.c2 .num{color:var(--brass)}.c2 .more{border-color:var(--brass)}
.c3{background:#EDFAF7}.c3 .num{color:var(--tide)}.c3 .more{border-color:var(--tide)}
.c4{background:#FFF3F0}.c4 .num{color:var(--coral)}.c4 .more{border-color:var(--coral)}
.mock{background:#fff;border-radius:14px;border:1px solid rgba(14,26,58,.08);padding:14px;font-size:12.5px;min-width:0}
.payhead{display:flex;justify-content:space-between;align-items:baseline;gap:8px}
.payhead b{font-family:var(--display);font-size:22px;font-variant-numeric:tabular-nums}
.paybox{display:grid;gap:10px}
.methods{display:flex;gap:6px;flex-wrap:wrap}
.methods span{border:1px solid var(--line);border-radius:8px;padding:5px 9px;font-weight:600;font-size:11.5px}
.methods span.on{border-color:var(--cobalt);background:#EEF1FF;color:var(--cobalt)}
.paybtn{background:var(--cobalt);color:#fff;text-align:center;border-radius:10px;padding:11px;font-weight:600}
.gen-in{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.gen-in div{background:var(--mist);border-radius:8px;padding:7px 9px;min-width:0}
.gen-in small{display:block;font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
.gen-in span{font-weight:600;font-size:12px}
.gen-out{margin-top:12px;display:grid;gap:6px}
.gen-out h5{margin:0;font-family:var(--display);font-size:13px}
.gen-out p{color:#3B4666;font-size:12px}
.typing{display:inline-block;width:6px;height:13px;background:var(--brass);vertical-align:-2px;animation:blink 1s steps(2) infinite}
@keyframes blink{50%{opacity:0}}
.qs{display:grid;gap:6px;margin-top:10px}
.q{background:var(--mist);border-radius:8px;padding:8px 10px;font-size:12px}
.q em{font-style:normal;font-weight:600;color:#8F610E;margin-right:6px}
.phone{max-width:280px;margin:0 auto;border-radius:24px;border:6px solid var(--ink);background:#fff;padding:14px 12px 16px;display:grid;gap:10px}
.ph-top{display:flex;align-items:center;gap:10px}
.avatar{width:34px;height:34px;border-radius:50%;background:var(--ink);display:grid;place-items:center;color:#fff;font-weight:600;font-size:12px;flex:none}
.ph-top b{display:block;font-size:13px}
.ph-top span{font-size:11px;color:var(--muted)}
.ph-tiles{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.ph-tiles div{background:var(--mist);border-radius:10px;padding:9px}
.ph-tiles b{display:block;font-family:var(--display);font-size:16px}
.ph-tiles span{font-size:10.5px;color:var(--muted)}
.notice{background:#EDFAF7;border-radius:10px;padding:9px 10px;font-size:11.5px}
.ops{list-style:none;margin:0;padding:0;border-top:1px solid rgba(14,26,58,.1)}
.ops li{display:flex;justify-content:space-between;gap:16px;padding:13px 0;border-bottom:1px solid rgba(14,26,58,.1);font-size:16px;font-weight:600}
.ops li span{font-weight:400;color:var(--muted);font-size:14px;text-align:right}

/* ---------- Roles ---------- */
.roles{padding-block:56px 72px}
.role-list{list-style:none;margin:28px 0 0;padding:0;display:grid}
.role-list li{padding:20px 0;border-top:1px solid var(--line);display:grid;gap:4px}
.role-list li:last-child{border-bottom:1px solid var(--line)}
.role-list h3{font-size:19px;font-weight:700}
.role-list p{color:var(--muted);font-size:16px}

/* ---------- Steps ---------- */
.steps{background:var(--mist);padding-block:72px}
.step-list{list-style:none;padding:0;margin:32px 0 0;display:grid;gap:28px}
.step-list li{display:grid;grid-template-columns:40px 1fr;gap:14px}
.step-list .n{font-family:var(--display);font-weight:600;font-size:28px;line-height:1;color:var(--cobalt)}
.step-list h3{font-size:19px}
.step-list p{color:var(--muted);font-size:16px;margin-top:6px}

/* ---------- CTA ---------- */
.reach{padding-block:72px}
.reach-card{border-radius:24px;color:#fff;padding:48px 24px;text-align:center;background:linear-gradient(180deg,var(--hero-a),var(--hero-b) 60%,var(--hero-c))}
.reach-card h2{font-size:clamp(30px,8vw,48px);text-transform:uppercase;font-weight:700;line-height:1.15}
.reach-card p{color:rgba(255,255,255,.86);font-size:17px;max-width:36ch;margin:18px auto 0}
.reach-card .hero-ctas{margin-top:32px}
.contact-lines{display:grid;gap:4px;margin-top:28px;font-size:15px;color:rgba(255,255,255,.8)}
.contact-lines b{color:#fff;font-weight:600;user-select:all}
.note{display:block;font-size:12px;color:rgba(255,255,255,.55);margin-top:8px}

/* ---------- Footer ---------- */
footer{background:var(--ink);color:#C2CBE4;padding-block:64px calc(96px + env(safe-area-inset-bottom,0px))}
.f-grid{display:grid;gap:44px}
.f-col h4{margin:0 0 14px;font-family:var(--display);font-size:20px;font-weight:700;letter-spacing:.02em;text-transform:uppercase;color:#fff}
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
.fab{position:fixed;right:16px;bottom:calc(16px + env(safe-area-inset-bottom,0px));z-index:30;width:54px;height:54px;border-radius:18px 18px 4px 18px;background:var(--ink);color:#fff;display:grid;place-items:center;border:0;box-shadow:0 10px 24px -8px rgba(12,23,56,.6);cursor:pointer}
.chat{position:fixed;right:16px;bottom:calc(80px + env(safe-area-inset-bottom,0px));z-index:31;width:min(340px,calc(100vw - 32px));background:#fff;border-radius:18px;box-shadow:0 24px 60px -18px rgba(12,23,56,.5);overflow:hidden;border:1px solid var(--line)}
.chat-h{background:var(--ink);color:#fff;padding:16px 18px}
.chat-h b{font-family:var(--display);font-size:16px}
.chat-h p{font-size:13px;color:#B6C0DC}
.chat-b{padding:10px 18px 14px}
.chat-b a{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line);font-weight:600;font-size:14px}
.chat-b a:last-child{border-bottom:0}

/* ---------- Desktop ---------- */
@media (min-width:900px){
  :root{--gutter:32px;--hdr:80px}
  .nav,.hdr-cta{display:flex}
  .hdr-cta{gap:10px;align-items:center}
  .burger{display:none}
  .hero canvas{width:1150px;height:1150px;bottom:-720px}
  .hero .wrap{padding-top:calc(var(--hdr) + 110px);padding-bottom:240px}
  .hero p{max-width:46ch}
  .hero-ctas{grid-template-columns:auto;justify-content:center}
  .types{margin-top:-100px}
  .types-card{padding:32px 40px}
  .types-list{grid-template-columns:repeat(6,1fr);column-gap:0}
  .types-list li{text-align:center;border-top:0;border-left:1px solid var(--line);padding:6px 0}
  .types-list li:first-child{border-left:0}
  .statement .grid2{display:grid;grid-template-columns:1.3fr 1fr;gap:56px;align-items:end}
  .dash{grid-template-columns:150px 1fr}
  .dash-side{display:grid;gap:4px;align-content:start;font-size:12px;font-weight:500;color:var(--muted)}
  .dash-side span{padding:7px 10px;border-radius:8px}
  .dash-side span.on{background:#E8ECFF;color:var(--cobalt);font-weight:600}
  .kpis{grid-template-columns:repeat(4,1fr)}
  .dash-two{grid-template-columns:1.4fr 1fr}
  .chartpanel{display:block}
  .cards{grid-template-columns:1fr 1fr;gap:28px}
  .card{padding:44px 40px 36px}
  .role-list{grid-template-columns:repeat(4,1fr);column-gap:32px}
  .role-list li,.role-list li:last-child{border-bottom:1px solid var(--line)}
  .step-list{grid-template-columns:repeat(3,1fr);gap:40px}
  .step-list li{grid-template-columns:1fr}
  .reach-card{padding:80px 48px}
  .reach-card .hero-ctas{grid-template-columns:auto auto;justify-content:center}
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
      <svg width="28" height="28" viewBox="0 0 30 30" aria-hidden="true"><rect width="30" height="30" rx="7" fill="#fff"/><path d="M9 19.6c.8 1.9 3.2 2.9 6.2 2.9 3.6 0 5.8-1.5 5.8-3.6 0-4.9-11.6-2.7-11.6-7.6 0-2 2.2-3.3 5.4-3.3 2.6 0 4.6.9 5.4 2.6" fill="none" stroke="#2A2F9E" stroke-width="2.6" stroke-linecap="round"/></svg>
      <b>School<span>Runs</span></b>
    </a>
    <nav class="nav" aria-label="Main">
      <a href="#products">Products</a>
      <a href="#roles">Who it's for</a>
      <a href="#how">How it works</a>
      <a href="#contact">Contact</a>
    </nav>
    <div class="hdr-cta">
      <a href="{{ route('login') }}" class="btn btn-line btn-sm">Log in</a>
      <a href="#register" class="btn btn-white btn-sm">Register your school</a>
    </div>
    <button class="burger" id="openMenu" aria-label="Open menu" aria-controls="menu" aria-expanded="false">
      <svg width="28" height="20" viewBox="0 0 28 20" aria-hidden="true"><path d="M0 2h28M0 10h28M0 18h28" stroke="#fff" stroke-width="2.4"/></svg>
    </button>
  </div>
</header>

<!-- ============ MOBILE MENU ============ -->
<div class="menu" id="menu" hidden role="dialog" aria-modal="true" aria-label="Menu">
  <div class="wrap">
    <div class="top">
      <span class="logo">
        <svg width="28" height="28" viewBox="0 0 30 30" aria-hidden="true"><rect width="30" height="30" rx="7" fill="#fff"/><path d="M9 19.6c.8 1.9 3.2 2.9 6.2 2.9 3.6 0 5.8-1.5 5.8-3.6 0-4.9-11.6-2.7-11.6-7.6 0-2 2.2-3.3 5.4-3.3 2.6 0 4.6.9 5.4 2.6" fill="none" stroke="#0C1738" stroke-width="2.6" stroke-linecap="round"/></svg>
        <b>School<span>Runs</span></b>
      </span>
      <button class="burger" id="closeMenu" aria-label="Close menu">
        <svg width="22" height="22" viewBox="0 0 22 22" aria-hidden="true"><path d="M2 2l18 18M20 2L2 20" stroke="#fff" stroke-width="2.4"/></svg>
      </button>
    </div>
    <ul>
      <li><a class="m-item" href="#about" data-close>About Us</a></li>
      <li>
        <button class="m-item" id="prodToggle" aria-expanded="false" aria-controls="prodSub">Products
          <svg width="18" height="11" viewBox="0 0 20 12" aria-hidden="true"><path d="M2 2l8 8 8-8" fill="none" stroke="#fff" stroke-width="2.4"/></svg>
        </button>
        <div class="m-sub" id="prodSub" hidden>
          <div class="m-group">
            <div class="m-label">Finance &amp; Payments</div>
            <a href="#p-fees" data-close>Online Fee Payments</a>
            <a href="#p-fees" data-close>Billing &amp; Invoicing</a>
          </div>
          <div class="m-group">
            <div class="m-label">AI Teaching Suite</div>
            <a href="#p-ai" data-close>Lesson Note Generator</a>
            <a href="#p-ai" data-close>Exam Question Bank</a>
          </div>
          <div class="m-group">
            <div class="m-label">Portals</div>
            <a href="#p-portals" data-close>Parents</a>
            <a href="#p-portals" data-close>Students</a>
            <a href="#p-portals" data-close>Staff &amp; HR</a>
          </div>
          <div class="m-group">
            <div class="m-label">School Operations</div>
            <a href="#p-ops" data-close>Results &amp; Report Cards</a>
            <a href="#p-ops" data-close>Attendance</a>
            <a href="#p-ops" data-close>Admissions</a>
            <a href="#p-ops" data-close>Library, Transport &amp; Hostel</a>
          </div>
        </div>
      </li>
      <li><a class="m-item" href="#how" data-close>How it works</a></li>
            <li><a class="m-item" href="{{ route('login') }}" data-close>Log in</a></li>
    </ul>
    <a href="#register" class="btn btn-white btn-block" data-close>Register your school</a>
  </div>
</div>

<main id="top">
<!-- ============ HERO ============ -->
<section class="hero">
  <canvas id="globe" width="1000" height="1000" aria-hidden="true"></canvas>
  <div class="wrap">
    <h1>The school management platform built for serious schools</h1>
    <p>SchoolRuns brings fees, results, attendance, admissions and AI-assisted teaching into one secure system for proprietors, staff and parents.</p>
    <div class="hero-ctas">
      <a href="#register" class="btn btn-white">Register your school
        <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="9" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 10h7M10.5 7l3 3-3 3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
      </a>
    </div>
    <a href="#contact" class="alt">or book a demo with our team</a>
  </div>
</section>

<!-- ============ WHITE CARD ============ -->
<section class="types" id="about">
  <div class="wrap">
    <div class="types-card">
      <h2>Built for every kind of school</h2>
      <ul class="types-list">
        <li>Nursery</li><li>Primary</li><li>Secondary</li><li>International</li><li>Boarding</li><li>School groups</li>
      </ul>
    </div>
  </div>
</section>

<!-- ============ STATEMENT ============ -->
<section class="statement">
  <div class="wrap">
    <div class="grid2">
      <h2>A school runs on hundreds of small workflows. <span>SchoolRuns keeps every one of them moving, from the first admission form to the last report card of the session.</span></h2>
      <p class="lead">One record for every student. One ledger for every naira. One place for every parent to check in. Each person sees only what their role allows, and every change is logged.</p>
    </div>
    <div class="stage" aria-label="Preview of the SchoolRuns bursary dashboard">
      <div class="screen">
        <div class="chrome"><i></i><i></i><i></i><span>app.schoolruns.com/bursary</span></div>
        <div class="dash">
          <div class="dash-side">
            <span>Overview</span><span class="on">Bursary</span><span>Students</span><span>Results</span><span>Attendance</span><span>Admissions</span><span>Staff</span>
          </div>
          <div class="dash-main">
            <div class="kpis">
              <div class="kpi"><span>Collected, 1st term</span><b>₦48.2m</b><div class="bar"><i style="width:72%;background:var(--tide)"></i></div></div>
              <div class="kpi"><span>Outstanding</span><b>₦18.7m</b><div class="bar"><i style="width:28%;background:var(--coral)"></i></div></div>
              <div class="kpi"><span>Paid online</span><b>81%</b><div class="bar"><i style="width:81%;background:var(--cobalt)"></i></div></div>
              <div class="kpi"><span>Attendance today</span><b>94.6%</b><div class="bar"><i style="width:94%;background:var(--brass)"></i></div></div>
            </div>
            <div class="dash-two">
              <div class="panel">
                <h4>Recent payments <span class="sample">Sample data</span></h4>
                <div class="row"><span>JSS 2B · Tuition</span><span class="amt">₦185,000</span><span class="pill ok">Paid</span></div>
                <div class="row"><span>SS 1A · Tuition + Bus</span><span class="amt">₦96,500</span><span class="pill part">Part-paid</span></div>
                <div class="row"><span>Primary 4 · Uniform</span><span class="amt">₦32,000</span><span class="pill ok">Paid</span></div>
                <div class="row"><span>SS 3C · Exam levy</span><span class="amt">₦45,000</span><span class="pill due">Overdue</span></div>
              </div>
              <div class="panel chartpanel">
                <h4>Collections by week <span>1st term</span></h4>
                <svg viewBox="0 0 260 110" width="100%" height="110" role="img" aria-label="Sample weekly collections bar chart">
                  <rect x="0" y="99" width="260" height="1" fill="#E4E8F2"/>
                  <g fill="#3346E0"><rect x="6" y="20" width="18" height="79" rx="3"/><rect x="38" y="38" width="18" height="61" rx="3"/><rect x="70" y="52" width="18" height="47" rx="3"/><rect x="102" y="62" width="18" height="37" rx="3"/><rect x="134" y="70" width="18" height="29" rx="3"/><rect x="166" y="66" width="18" height="33" rx="3"/><rect x="198" y="76" width="18" height="23" rx="3"/></g>
                  <rect x="230" y="72" width="18" height="27" rx="3" fill="#0F9F8F"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ PRODUCTS ============ -->
<section class="pillars" id="products">
  <div class="wrap">
    <div class="sec-head">
      <h2>Everything your school runs on</h2>
      <p>Four connected products that share one student record, so nothing is typed twice.</p>
    </div>
    <div class="cards">
      <article class="card c1" id="p-fees">
        <div class="num">01</div>
        <h3>Online Fee Payments</h3>
        <p class="desc">Parents pay by <b>card, bank transfer or USSD</b>. Each payment is matched to the right invoice automatically, so your bursar no longer chases receipts.</p>
        <div class="mock paybox" aria-label="Example parent fee payment screen">
          <div class="payhead"><span>1st term fees · Adaeze O.</span><span class="sample">Sample</span></div>
          <div class="payhead"><b>₦185,000</b><span class="pill part">₦60,000 paid</span></div>
          <div class="methods"><span class="on">Card</span><span>Bank transfer</span><span>USSD</span><span>Instalment</span></div>
          <div class="paybtn">Pay ₦125,000 balance</div>
        </div>
        <ul class="feat">
          <li>Bulk invoices by class, term or session</li>
          <li>Sibling discounts, scholarships and instalments</li>
          <li>Automatic receipts and overdue reminders</li>
        </ul>
        <a href="#p-fees" class="more">Learn more <svg width="22" height="10" viewBox="0 0 22 10" aria-hidden="true"><path d="M0 5h20M16 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg></a>
      </article>

      <article class="card c2" id="p-ai">
        <div class="num">02</div>
        <h3>AI Lesson Notes &amp; Exam Questions</h3>
        <p class="desc">A teacher picks the subject, class and topic. SchoolRuns drafts a <b>full lesson note</b> and a <b>set of exam questions</b> that follow the scheme of work. The teacher reviews and approves before anything is used.</p>
        <div class="mock" aria-label="Example AI lesson note generator">
          <div class="gen-in">
            <div><small>Subject</small><span>Basic Science</span></div>
            <div><small>Class</small><span>JSS 1</span></div>
            <div><small>Term · Week</small><span>1st · Week 4</span></div>
            <div><small>Topic</small><span>States of matter</span></div>
          </div>
          <div class="gen-out">
            <h5>Lesson note · 40 minutes</h5>
            <p><b>Objectives:</b> By the end of the lesson, students should be able to name the three states of matter and give two everyday examples of each.</p>
            <p><b>Introduction:</b> Show ice, water and steam from a kettle<span class="typing"></span></p>
          </div>
          <div class="qs">
            <div class="q"><em>Objective</em>Which of these is a gas at room temperature?</div>
            <div class="q"><em>Theory</em>Explain what happens to particles when ice melts.</div>
          </div>
        </div>
        <ul class="feat">
          <li>Lesson notes in your school's own format</li>
          <li>Objective and theory questions with marking guides</li>
          <li>Head of department approval before publishing</li>
        </ul>
        <a href="#p-ai" class="more">Learn more <svg width="22" height="10" viewBox="0 0 22 10" aria-hidden="true"><path d="M0 5h20M16 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg></a>
      </article>

      <article class="card c3" id="p-portals">
        <div class="num">03</div>
        <h3>Portals for Parents, Students &amp; Staff</h3>
        <p class="desc">Everyone signs in to their own view. <b>Parents</b> see results, fees and attendance. <b>Students</b> see homework and timetables. <b>Staff</b> see their classes, leave and payslips.</p>
        <div class="mock" style="background:#F6FCFA">
          <div class="phone" aria-label="Example parent portal on a phone">
            <div class="ph-top"><div class="avatar">AO</div><div><b>Adaeze Okafor</b><span>JSS 2B · 1st term</span></div></div>
            <div class="ph-tiles">
              <div><b>A</b><span>Mid-term average</span></div>
              <div><b>97%</b><span>Attendance</span></div>
              <div><b>₦125k</b><span>Fees balance</span></div>
              <div><b>3</b><span>Homework due</span></div>
            </div>
            <div class="notice">The 1st term report card is ready to view.</div>
            <span class="sample" style="justify-self:center">Sample data</span>
          </div>
        </div>
        <ul class="feat">
          <li>Works in any phone browser and uses little data</li>
          <li>One parent login for all their children</li>
          <li>School announcements by email and SMS</li>
        </ul>
        <a href="#p-portals" class="more">Learn more <svg width="22" height="10" viewBox="0 0 22 10" aria-hidden="true"><path d="M0 5h20M16 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg></a>
      </article>

      <article class="card c4" id="p-ops">
        <div class="num">04</div>
        <h3>School Operations</h3>
        <p class="desc">The daily running of the school in one place. Scores entered by teachers become <b>report cards</b> automatically, and new admissions go straight into class lists.</p>
        <ul class="ops">
          <li>Results &amp; report cards <span>CA, exams, positions</span></li>
          <li>Attendance <span>Daily registers</span></li>
          <li>Admissions <span>Online forms</span></li>
          <li>Timetable <span>No class clashes</span></li>
          <li>Library <span>Loans and returns</span></li>
          <li>Transport &amp; hostel <span>Routes and rooms</span></li>
        </ul>
        <a href="#p-ops" class="more">Learn more <svg width="22" height="10" viewBox="0 0 22 10" aria-hidden="true"><path d="M0 5h20M16 1l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.4"/></svg></a>
      </article>
    </div>
  </div>
</section>

<!-- ============ ROLES ============ -->
<section class="roles" id="roles">
  <div class="wrap">
    <div class="sec-head"><h2>One platform, every role</h2></div>
    <ul class="role-list">
      <li><h3>Proprietors</h3><p>See fees, enrolment and performance across one school or a whole group.</p></li>
      <li><h3>Bursars</h3><p>Invoices, online payments and debtor lists that stay up to date on their own.</p></li>
      <li><h3>Teachers</h3><p>Lesson notes, question banks, registers and score entry in one place.</p></li>
      <li><h3>Parents</h3><p>Results, fees, attendance and school news on their phone.</p></li>
    </ul>
  </div>
</section>

<!-- ============ STEPS ============ -->
<section class="steps" id="how">
  <div class="wrap">
    <div class="sec-head">
      <h2>Get started in three steps</h2>
      <p>Your school can register itself today. Our team helps you bring your records across.</p>
    </div>
    <ol class="step-list">
      <li><div class="n">1</div><div><h3>Register your school</h3><p>Enter your school's name, address and an admin contact. Your private school account is created straight away.</p></div></li>
      <li><div class="n">2</div><div><h3>Set up your session</h3><p>Add classes, subjects, fee items and grading. Import students from a spreadsheet.</p></div></li>
      <li><div class="n">3</div><div><h3>Invite your people</h3><p>Send logins to staff and parents, then start taking fees and attendance.</p></div></li>
    </ol>
  </div>
</section>

<!-- ============ REACH OUT ============ -->
<section class="reach" id="register">
  <div class="wrap">
    <div class="reach-card" id="contact">
      <h2>Reach out to us</h2>
      <p>Register your school in minutes, or book a guided demo with our team first.</p>
      <div class="hero-ctas">
        <a href="#register" class="btn btn-white">Register your school</a>
        <a href="#contact" class="btn btn-line">Book a demo</a>
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

<!-- ============ FOOTER ============ -->
<footer>
  <div class="wrap">
    <div class="f-grid">
      <div class="f-col">
        <h4>Our Products</h4>
        <h5>Finance</h5>
        <a href="#p-fees">Online Fee Payments</a>
        <h5>Teaching</h5>
        <a href="#p-ai">AI Lesson Notes</a>
        <a href="#p-ai">Exam Question Bank</a>
        <h5>Portals &amp; Operations</h5>
        <a href="#p-portals">Parent &amp; Student Portals</a>
        <a href="#p-ops">Results &amp; Report Cards</a>
      </div>
      <div class="f-col">
        <h4>Legal</h4>
        <a href="#top">Terms of Use</a>
        <a href="#top">Privacy Notice</a>
        <a href="#top">Data Protection Policy</a>
        <a href="#top">Information Security Policy</a>
      </div>
      <div class="f-col">
        <h4>Offices</h4>
        <div class="addr"><b>Lagos</b><i>Address to be added</i></div>
        <div class="addr"><b>Abuja</b><i>Address to be added</i></div>
      </div>
      <div class="f-col">
        <h4>Follow Us</h4>
        <div class="social">
          <a href="#" aria-label="LinkedIn"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4.98 3.5a2.5 2.5 0 110 5 2.5 2.5 0 010-5zM3 9.5h4V21H3zM9.5 9.5h3.8v1.6h.06c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.77 2.65 4.77 6.1V21h-4v-5.1c0-1.22-.02-2.78-1.7-2.78-1.7 0-1.95 1.32-1.95 2.7V21h-4z"/></svg></a>
          <a href="#" aria-label="X"><svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.2 2.5h3.4l-7.4 8.4 8.7 11.6h-6.8l-5.3-7-6.1 7H1.3l7.9-9L.9 2.5h7l4.8 6.4zm-1.2 18h1.9L7.1 4.4H5.1z"/></svg></a>
          <a href="#" aria-label="Instagram"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor"/></svg></a>
          <a href="#" aria-label="Facebook"><svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5h1.65V3.6c-.3 0-1.27-.1-2.4-.1-2.38 0-4 1.45-4 4.1v2.3H7.6V13h2.7v8z"/></svg></a>
        </div>
      </div>
    </div>
    <div class="f-base">© 2026 SchoolRuns. All rights reserved.</div>
  </div>
</footer>

<!-- ============ CHAT ============ -->
<div class="chat" id="chat" hidden role="dialog" aria-label="Chat with SchoolRuns">
  <div class="chat-h"><b>How can we help?</b><p>Our team replies during school hours.</p></div>
  <div class="chat-b">
    <a href="#register">Register my school <span>→</span></a>
    <a href="#contact">Book a demo <span>→</span></a>
    <a href="#p-fees">Ask about fees and payments <span>→</span></a>
  </div>
</div>
<button class="fab" id="fab" aria-label="Open chat" aria-controls="chat" aria-expanded="false">
  <svg width="24" height="24" viewBox="0 0 26 26" aria-hidden="true"><rect x="2" y="4" width="22" height="16" rx="6" fill="#fff"/><path d="M8 10.5h10M8 14h6" stroke="#0C1738" stroke-width="2" stroke-linecap="round"/></svg>
</button>
@verbatim
<script>
(function(){
  // Header turns navy once the page scrolls past the top
  var hdr=document.getElementById('hdr');
  function onScroll(){hdr.classList.toggle('solid',window.scrollY>24)}
  onScroll();window.addEventListener('scroll',onScroll,{passive:true});

  // Menu
  var menu=document.getElementById('menu'),openB=document.getElementById('openMenu'),closeB=document.getElementById('closeMenu');
  function setMenu(o){menu.hidden=!o;openB.setAttribute('aria-expanded',o);document.body.style.overflow=o?'hidden':'';if(o)closeB.focus();}
  openB.addEventListener('click',function(){setMenu(true)});
  closeB.addEventListener('click',function(){setMenu(false);openB.focus()});
  menu.querySelectorAll('[data-close]').forEach(function(a){a.addEventListener('click',function(){setMenu(false)})});
  var pt=document.getElementById('prodToggle'),ps=document.getElementById('prodSub');
  pt.addEventListener('click',function(){var o=pt.getAttribute('aria-expanded')!=='true';pt.setAttribute('aria-expanded',o);ps.hidden=!o});

  // Chat
  var chat=document.getElementById('chat'),fab=document.getElementById('fab');
  function toggleChat(o){chat.hidden=!o;fab.setAttribute('aria-expanded',o)}
  fab.addEventListener('click',function(){toggleChat(chat.hidden)});
  chat.querySelectorAll('a').forEach(function(a){a.addEventListener('click',function(){toggleChat(false)})});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){if(!menu.hidden)setMenu(false);if(!chat.hidden)toggleChat(false)}});

  // Dotted globe
  var c=document.getElementById('globe'),ctx=c.getContext('2d'),W=c.width,R=W*0.47,cx=W/2,cy=W/2;
  var reduce=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var pts=[];
  for(var lat=-84;lat<=84;lat+=6){
    var r=Math.cos(lat*Math.PI/180),n=Math.max(8,Math.round(54*r));
    for(var i=0;i<n;i++)pts.push([lat*Math.PI/180,(i/n)*Math.PI*2]);
  }
  function draw(t){
    ctx.clearRect(0,0,W,W);
    var rot=t*0.00004,tilt=0.32;
    for(var k=0;k<pts.length;k++){
      var la=pts[k][0],lo=pts[k][1]+rot;
      var x=Math.cos(la)*Math.sin(lo),y=Math.sin(la),z=Math.cos(la)*Math.cos(lo);
      var y2=y*Math.cos(tilt)-z*Math.sin(tilt),z2=y*Math.sin(tilt)+z*Math.cos(tilt);
      if(z2<0)continue;
      var a=0.22+0.6*z2;
      ctx.fillStyle='rgba(214,222,255,'+a.toFixed(3)+')';
      // dots flatten toward the rim like the reference
      var rx=13*(0.35+0.65*Math.sqrt(1-x*x)),ry=8*(0.35+0.65*z2);
      ctx.beginPath();ctx.ellipse(cx+x*R,cy-y2*R,rx,ry,0,0,Math.PI*2);ctx.fill();
    }
    if(!reduce)requestAnimationFrame(draw);
  }
  requestAnimationFrame(draw);
})();
</script>
@endverbatim
</body>
</html>
