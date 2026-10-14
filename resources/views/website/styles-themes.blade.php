@verbatim
/* ===== brand-name small line (used by Statement) ===== */
.brand small{display:block;margin-top:.25rem;font:700 .68rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;opacity:.75;white-space:normal}
.hero em,h1 em,h2 em{font-style:italic}
.wave{position:absolute;left:0;right:0;bottom:-1px;width:100%;height:3.5rem;fill:var(--bg);display:block}

/* ================= MODERN: floating bar, split banner, bento cards, timeline ================= */
[data-theme=modern]{--hdr-h:5.4rem}
[data-theme=modern] .hdr{background:transparent;border:0;box-shadow:none;padding:.8rem 0 0;pointer-events:none}
[data-theme=modern] .hdr-in{pointer-events:auto;min-height:3.7rem;padding:.4rem .5rem .4rem 1.15rem;background:color-mix(in srgb,var(--bg) 84%,transparent);-webkit-backdrop-filter:blur(16px);backdrop-filter:blur(16px);border:1px solid var(--line);border-radius:999px;box-shadow:0 14px 40px -20px rgba(15,23,42,.45)}
[data-theme=modern] .brand span{font:800 1.12rem/1.1 var(--body-font);letter-spacing:-.025em}
[data-theme=modern] .burger{width:2.7rem;height:2.7rem;border-radius:50%;background:var(--soft)}
[data-theme=modern] .menu{inset:var(--hdr-h) .75rem auto .75rem;border-radius:1.5rem;padding:1rem 1.25rem 1.25rem;background:var(--bg);color:var(--text);box-shadow:0 30px 60px -20px rgba(15,23,42,.5)}
[data-theme=modern] .menu a{font:700 1.15rem/1.2 var(--body-font);border-bottom:1px solid var(--line);padding:.85rem 0}
[data-theme=modern] .menu .btn{color:var(--brand-ink)}
@media (min-width:60rem){
  [data-theme=modern] .menu,[data-theme=modern] .menu-open .menu{inset:auto;background:none;box-shadow:none;border-radius:0;padding:0}
  [data-theme=modern] .menu a{font:600 .92rem/1 var(--body-font);border:0;padding:.7rem .85rem}
  [data-theme=modern] .menu a:hover{background:var(--soft)}
  [data-theme=modern] .menu a[aria-current]{background:color-mix(in srgb,var(--brand) 12%,var(--bg));color:var(--link)}
  [data-theme=modern] .menu .btn{padding:.55rem 1.3rem}
}
[data-theme=modern] .hero--modern{min-height:0;color:var(--text);background:
    radial-gradient(46rem 26rem at 100% -10%,color-mix(in srgb,var(--brand) 16%,transparent),transparent 62%),
    radial-gradient(34rem 22rem at -5% 105%,color-mix(in srgb,var(--brand2) 22%,transparent),transparent 62%),var(--bg)}
[data-theme=modern] .hero--modern::before,[data-theme=modern] .hero--modern::after{display:none}
[data-theme=modern] .hero--modern .wrap{padding-top:clamp(1.5rem,4vw,3rem);padding-bottom:clamp(3rem,8vw,6rem)}
.hero-grid{display:grid;gap:2.5rem;align-items:center}
@media (min-width:56rem){.hero-grid{grid-template-columns:1.12fr .88fr;gap:3rem}}
[data-theme=modern] .hero-tag{border:0;background:color-mix(in srgb,var(--brand) 10%,var(--bg));color:var(--link);-webkit-backdrop-filter:none;backdrop-filter:none}
[data-theme=modern] .hero h1,[data-theme=modern] .hero h2{max-width:14ch;color:var(--text);font-weight:800;font-size:clamp(2.6rem,7.2vw,5rem);letter-spacing:-.045em;line-height:1.02;text-shadow:none}
[data-theme=modern] .hero h1 em,[data-theme=modern] .hero h2 em{font-style:normal;background:linear-gradient(90deg,var(--brand),color-mix(in srgb,var(--brand) 40%,var(--brand2)));-webkit-background-clip:text;background-clip:text;color:transparent}
[data-theme=modern] .hero-sub{color:var(--muted);opacity:1}
[data-theme=modern] .hero .btn--solid{background:var(--brand);color:var(--brand-ink);box-shadow:0 14px 30px -14px var(--brand)}
[data-theme=modern] .hero .btn--ghost{color:var(--text);border-color:var(--line);background:var(--bg)}
[data-theme=modern] .hero .btn--ghost:hover{background:var(--soft)}
.hero-art{position:relative;aspect-ratio:5/4;max-height:30rem;width:100%}
@media (min-width:56rem){.hero-art{aspect-ratio:1/1.02;max-height:34rem}}
.hero-art img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:2rem;box-shadow:0 40px 70px -34px rgba(15,23,42,.6)}
.hero-art .art-a,.hero-art .art-b,.hero-art .art-c{position:absolute;display:block}
.art-a{inset:8% 4% 6% 12%;border-radius:2.2rem;background:linear-gradient(145deg,var(--brand),color-mix(in srgb,var(--brand) 55%,#000));box-shadow:0 40px 70px -34px var(--brand);transform:rotate(3deg)}
.art-b{width:34%;aspect-ratio:1;right:-2%;top:2%;border-radius:50%;background:var(--brand2);opacity:.95}
.art-c{width:46%;aspect-ratio:1;left:-3%;bottom:0;border-radius:2rem;border:10px solid color-mix(in srgb,var(--brand2) 55%,transparent);transform:rotate(-8deg)}
.art-chip{position:absolute;display:grid;place-items:center;width:4.4rem;height:4.4rem;border-radius:1.2rem;background:var(--bg);color:var(--link);box-shadow:0 20px 40px -18px rgba(15,23,42,.5)}
.art-chip .ico{width:1.9rem;height:1.9rem}
.art-chip--1{left:2%;top:18%}.art-chip--2{right:6%;top:46%}.art-chip--3{left:24%;bottom:6%}
[data-theme=modern] .actions{margin-top:0;padding-top:0}
[data-theme=modern] .sec{padding:clamp(3.5rem,9vw,6.5rem) 0}
[data-theme=modern] .sec.page-head{padding-bottom:1rem}
[data-theme=modern] .sec.sec--tight{padding-top:1rem}
[data-theme=modern] .sec--soft{background:var(--soft);border-radius:2rem;margin:0 .75rem}
[data-theme=modern] .eyebrow{padding:.4rem .8rem;border-radius:999px;background:color-mix(in srgb,var(--brand) 10%,var(--bg))}
[data-theme=modern] .eyebrow::before{display:none}
[data-theme=modern] .card{border:0;border-radius:1.5rem;box-shadow:0 1px 0 var(--line),0 24px 40px -30px rgba(15,23,42,.5)}
[data-theme=modern] .card .card-ico{background:color-mix(in srgb,var(--brand) 12%,var(--bg));border-radius:1rem}
@media (min-width:60rem){
  [data-theme=modern] .cards{grid-template-columns:repeat(4,1fr)}
  [data-theme=modern] .cards .card:nth-child(1){grid-column:span 2;grid-row:span 2;justify-content:flex-end;background:linear-gradient(150deg,var(--brand),color-mix(in srgb,var(--brand) 50%,#000));color:var(--brand-ink)}
  [data-theme=modern] .cards .card:nth-child(1) p{color:inherit;opacity:.9}
  [data-theme=modern] .cards .card:nth-child(1) h3{font-size:2rem}
  [data-theme=modern] .cards .card:nth-child(1) .card-ico{background:rgba(255,255,255,.16);color:inherit}
  [data-theme=modern] .cards .card:nth-child(4),[data-theme=modern] .cards .card:nth-child(5),[data-theme=modern] .cards .card:nth-child(6){grid-column:span 2}
}
[data-theme=modern] .steps li{background:none;border:0;padding:0 .5rem 0 0}
[data-theme=modern] .steps li::after{display:none!important}
[data-theme=modern] .step-n{width:3.3rem;height:3.3rem;border-radius:1.1rem;background:linear-gradient(135deg,var(--brand),color-mix(in srgb,var(--brand) 45%,var(--brand2)));color:#fff;box-shadow:0 16px 26px -14px var(--brand)}
@media (min-width:64rem){
  [data-theme=modern] .steps{position:relative}
  [data-theme=modern] .steps::before{content:"";position:absolute;left:1.6rem;right:3rem;top:1.65rem;height:2px;background:repeating-linear-gradient(90deg,color-mix(in srgb,var(--brand) 35%,transparent) 0 8px,transparent 8px 16px)}
  [data-theme=modern] .step-n{position:relative;z-index:1}
}
[data-theme=modern] .stats{background:var(--soft);border-radius:2rem;padding:1.5rem 0}
[data-theme=modern] .stat{border:0}
[data-theme=modern] .stat b{font-family:var(--body-font);font-weight:800;letter-spacing:-.04em;background:linear-gradient(90deg,var(--brand),color-mix(in srgb,var(--brand) 40%,var(--brand2)));-webkit-background-clip:text;background-clip:text;color:transparent}
[data-theme=modern] .news-item{border:0;border-radius:1.5rem;box-shadow:0 1px 0 var(--line),0 24px 40px -30px rgba(15,23,42,.5)}
[data-theme=modern] .cta{background:none;padding:clamp(2rem,5vw,3.5rem) 0}
[data-theme=modern] .cta .wrap{max-width:70rem;padding:clamp(2.5rem,7vw,4.5rem) clamp(1.25rem,5vw,3rem);border-radius:2rem;background:radial-gradient(30rem 16rem at 90% 0,color-mix(in srgb,var(--brand2) 45%,transparent),transparent 60%),linear-gradient(135deg,var(--brand),color-mix(in srgb,var(--brand) 50%,#000));color:var(--brand-ink);width:calc(100% - 1.5rem)}
[data-theme=modern] .faq details{border:0;background:var(--bg);border-radius:1.1rem;margin-bottom:.6rem;padding:0 1.25rem;box-shadow:0 1px 0 var(--line)}
[data-theme=modern] .ftr{margin:0 .75rem .75rem;border-radius:2rem;padding:3.5rem 0 2rem}
[data-theme=modern] .pagehero{border-radius:0 0 2rem 2rem}

/* ================= WARM: centred header, soft shapes, arch picture, colourful cards ================= */
[data-theme=warm]{--hdr-h:4.5rem}
[data-theme=warm] .hdr{background:var(--bg);border-bottom:0;box-shadow:none}
[data-theme=warm] .brand span{font:700 1.35rem/1.1 var(--head-font)}
@media (min-width:60rem){
  [data-theme=warm] .hdr-in{flex-direction:column;justify-content:center;gap:.35rem;padding:.9rem 0 .6rem}
  [data-theme=warm] .brand img{height:3.2rem;max-width:4.2rem}
  [data-theme=warm] .brand span{font-size:1.7rem}
  [data-theme=warm] .menu{justify-content:center;flex-wrap:wrap}
  [data-theme=warm] .menu a{font-weight:700;padding:.55rem 1rem}
  [data-theme=warm] .menu a:hover{background:var(--soft)}
  [data-theme=warm] .menu a[aria-current]{background:color-mix(in srgb,var(--brand2) 35%,var(--bg));color:var(--text)}
}
[data-theme=warm] .hero--warm{min-height:0;color:var(--text);text-align:center;background:linear-gradient(180deg,color-mix(in srgb,var(--brand2) 28%,var(--bg)) 0%,var(--bg) 100%)}
[data-theme=warm] .hero--warm::before,[data-theme=warm] .hero--warm::after{display:none}
[data-theme=warm] .hero--warm .wrap{padding-top:clamp(2.5rem,7vw,4.5rem);padding-bottom:clamp(4.5rem,10vw,7rem);position:relative;z-index:1;display:flex;flex-direction:column;align-items:center}
[data-theme=warm] .hero h1,[data-theme=warm] .hero h2{color:var(--text);max-width:18ch;text-shadow:none;font-size:clamp(2.4rem,7vw,4.4rem);font-weight:700}
[data-theme=warm] .hero h1 em,[data-theme=warm] .hero h2 em{color:var(--link)}
[data-theme=warm] .hero-sub{color:var(--muted);opacity:1;margin-left:auto;margin-right:auto}
[data-theme=warm] .hero-tag{border:0;background:var(--bg);color:var(--link);box-shadow:0 8px 20px -12px rgba(120,53,15,.5);-webkit-backdrop-filter:none;backdrop-filter:none}
[data-theme=warm] .hero .btns{justify-content:center}
[data-theme=warm] .hero .btn--solid{background:var(--brand);color:var(--brand-ink);box-shadow:0 14px 26px -14px var(--brand)}
[data-theme=warm] .hero .btn--ghost{color:var(--text);border-color:color-mix(in srgb,var(--text) 30%,transparent)}
[data-theme=warm] .hero .btn--ghost:hover{background:rgba(255,255,255,.6)}
.blob{position:absolute;border-radius:50%;z-index:0;pointer-events:none}
.blob--1{width:20rem;height:20rem;left:-6rem;top:-5rem;background:color-mix(in srgb,var(--brand2) 55%,transparent);filter:blur(2px)}
.blob--2{width:14rem;height:14rem;right:-3rem;top:3rem;background:color-mix(in srgb,var(--brand) 22%,transparent)}
.blob--3{width:9rem;height:9rem;left:12%;bottom:4rem;background:color-mix(in srgb,var(--brand) 14%,transparent)}
.hero-arch{margin-top:2.5rem;width:min(100%,24rem)}
.hero-arch img{width:100%;aspect-ratio:4/5;object-fit:cover;border-radius:999px 999px 2rem 2rem;border:8px solid var(--bg);box-shadow:0 30px 60px -30px rgba(120,53,15,.6)}
[data-theme=warm] .actions{margin-top:calc(-1 * 2.5rem)}
[data-theme=warm] .action{border-radius:1.5rem;box-shadow:0 18px 36px -22px rgba(120,53,15,.5)}
[data-theme=warm] .eyebrow{color:var(--link)}
[data-theme=warm] .eyebrow::before{width:.6rem;height:.6rem;border-radius:50%}
[data-theme=warm] .sec-head{text-align:center;margin-left:auto;margin-right:auto}
[data-theme=warm] .sec-head .eyebrow{justify-content:center}
[data-theme=warm] .sec--soft{position:relative;background:var(--soft)}
[data-theme=warm] .card{border:0;border-radius:2rem;text-align:center;box-shadow:0 18px 36px -26px rgba(120,53,15,.55)}
[data-theme=warm] .card .card-ico{margin-left:auto;margin-right:auto;width:4.2rem;height:4.2rem;border-radius:50% 50% 50% 18%;background:var(--tint,var(--brand2));color:var(--tint-ink,var(--text))}
[data-theme=warm] .card .card-ico .ico{width:2rem;height:2rem}
[data-theme=warm] .cards .card:nth-child(4n+1){--tint:color-mix(in srgb,var(--brand2) 55%,var(--bg))}
[data-theme=warm] .cards .card:nth-child(4n+2){--tint:color-mix(in srgb,var(--brand) 22%,var(--bg));--tint-ink:var(--link)}
[data-theme=warm] .cards .card:nth-child(4n+3){--tint:color-mix(in srgb,#34a56b 28%,var(--bg));--tint-ink:#166534}
[data-theme=warm] .cards .card:nth-child(4n+4){--tint:color-mix(in srgb,#4a90d9 28%,var(--bg));--tint-ink:#1d4f91}
[data-theme=warm] .card:hover{transform:translateY(-6px) rotate(-.6deg)}
[data-theme=warm] .card--plain::before{display:none}
[data-theme=warm] .steps li{background:var(--bg);border-radius:2rem;text-align:center;box-shadow:0 18px 36px -26px rgba(120,53,15,.55);border:0}
[data-theme=warm] .sec--soft .steps li{background:var(--bg)}
[data-theme=warm] .step-n{margin-left:auto;margin-right:auto;width:3.4rem;height:3.4rem;border-radius:50% 50% 50% 16%;background:var(--brand2);color:var(--brand2-ink);box-shadow:0 0 0 .5rem color-mix(in srgb,var(--brand2) 28%,transparent);font-size:1.3rem}
[data-theme=warm] .steps li:not(:last-child)::after{display:none!important}
[data-theme=warm] .stats{gap:1rem;padding:0}
[data-theme=warm] .stat{border:0;background:var(--soft);border-radius:2rem;padding:1.6rem .5rem;margin:.25rem}
[data-theme=warm] .stat b{color:var(--link)}
[data-theme=warm] .news-item{border:0;border-radius:2rem;box-shadow:0 18px 36px -26px rgba(120,53,15,.55)}
[data-theme=warm] .cta{position:relative;margin:0;background:linear-gradient(180deg,var(--brand),color-mix(in srgb,var(--brand) 80%,#000));border-radius:0;padding-top:clamp(4.5rem,9vw,7rem)}
[data-theme=warm] .cta::before{content:"";position:absolute;left:0;right:0;top:-1px;height:3.2rem;background:var(--cta-wave,var(--bg));-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 90' preserveAspectRatio='none'%3E%3Cpath d='M0 0h1440v30c-240 60-480 60-720 20S240-10 0 40z'/%3E%3C/svg%3E") center/100% 100% no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 90' preserveAspectRatio='none'%3E%3Cpath d='M0 0h1440v30c-240 60-480 60-720 20S240-10 0 40z'/%3E%3C/svg%3E") center/100% 100% no-repeat}
[data-theme=warm] .faq details{background:var(--bg);border:0;border-radius:1.4rem;margin-bottom:.7rem;padding:0 1.4rem;box-shadow:0 10px 24px -20px rgba(120,53,15,.6)}
[data-theme=warm] .faq{margin-left:auto;margin-right:auto}
[data-theme=warm] .ftr{background:color-mix(in srgb,var(--brand) 22%,#2a1a10);border-radius:2.5rem 2.5rem 0 0;margin-top:-1rem;position:relative}
[data-theme=warm] .pagehero{border-radius:0 0 3rem 3rem;text-align:center}
[data-theme=warm] .pagehero h1,[data-theme=warm] .pagehero p{margin-left:auto;margin-right:auto}

/* ================= STATEMENT: maroon and gold civic look. Fraunces headlines, Instrument Sans text, ring pattern, flat deep bands ================= */
[data-theme=statement]{--hdr-h:4.9rem;--rings:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='92' height='92'%3E%3Ccircle cx='46' cy='46' r='10' fill='none' stroke='%23ff9a9a' stroke-opacity='.2' stroke-width='1.6'/%3E%3C/svg%3E");--olive:#6b6a58;--pink:#ecd6d6}
[data-theme=statement] body,body[data-theme=statement]{overflow-x:clip;font-size:1rem;line-height:1.8}
[data-theme=statement] .wrap{padding:0 1.5rem}
[data-theme=statement] p{text-wrap:wrap}
[data-theme=statement] .hdr-in{position:relative}
[data-theme=statement] .burger{position:absolute;right:.45rem;top:50%;transform:translateY(-50%)}
[data-theme=statement] .brand{flex:1 1 auto;padding-right:2.4rem}
[data-theme=statement] h1,[data-theme=statement] h2,[data-theme=statement] h3{font-weight:700;letter-spacing:-.015em;font-optical-sizing:auto}
[data-theme=statement] .notice{background:var(--brand2);color:var(--deep)}
[data-theme=statement] .notice p{font:600 .9rem/1.55 var(--body-font)}
[data-theme=statement] .hdr{background:var(--bg);box-shadow:none;border-bottom:3px solid var(--brand);color:var(--brand)}
[data-theme=statement] .hdr-in{min-height:4.7rem;padding-top:1rem;padding-bottom:1rem}
[data-theme=statement] .brand{flex-direction:column;align-items:flex-start;gap:0}
[data-theme=statement] .brand span{display:block}
[data-theme=statement] .brand span{font:700 1.2rem/1.2 var(--head-font);letter-spacing:0;color:var(--brand);overflow:visible;text-overflow:clip}
[data-theme=statement] .brand small{margin-top:.5rem;color:var(--olive);opacity:1;font:600 .66rem/1.2 var(--body-font);letter-spacing:.2em}
[data-theme=statement] .burger{color:var(--brand)}
[data-theme=statement] .menu a{font-family:var(--head-font);font-weight:600;white-space:nowrap}
@media (min-width:60rem){
  [data-theme=statement] .brand{padding-right:0}
  [data-theme=statement] .menu{flex:none}
  [data-theme=statement] .menu a{font:700 .74rem/1 var(--body-font);letter-spacing:.1em;text-transform:uppercase;border-radius:0;padding:.7rem .6rem;border-bottom:3px solid transparent;color:var(--text)}
  [data-theme=statement] .menu a:hover{background:none;border-bottom-color:var(--brand2)}
  [data-theme=statement] .menu a[aria-current]{background:none;border-bottom-color:var(--brand);color:var(--brand)}
  [data-theme=statement] .menu .btn{border-radius:.3rem;border-bottom:0;letter-spacing:.06em;padding:.65rem 1.3rem;color:var(--brand-ink)}
}

/* hero: ring pattern on a maroon gradient, with an optional white details card */
.hero--statement{display:block;min-height:0;color:#fff;background:var(--rings) 0 0/92px 92px,linear-gradient(180deg,var(--brand) 0%,var(--deep) 100%)}
.hero--statement.hero--photo::before{background:color-mix(in srgb,var(--deep) 70%,transparent)}
.hero--statement .hero-img{opacity:.3;mix-blend-mode:luminosity}
[data-theme=statement] .hero .wrap{padding-top:6.5rem;padding-bottom:6rem}
.hero-grid{display:grid;gap:4rem}
.hero-kicker{margin:0 0 1.6rem;font:700 .74rem/1.65 var(--body-font);letter-spacing:.19em;text-transform:uppercase;color:var(--gold-lt)}
[data-theme=statement] .hero h1,[data-theme=statement] .hero h2{max-width:none;margin:0;font-size:clamp(2.9rem,15.1vw,6.4rem);font-weight:700;line-height:1.08;letter-spacing:0;text-shadow:none;color:#fff}
[data-theme=statement] .hero h1 em,[data-theme=statement] .hero h2 em{color:var(--gold-lt);font-weight:700;font-style:italic}
.hero-lead{margin:.9rem 0 0;font:italic 300 clamp(1.3rem,6.3vw,2.3rem)/1.6 var(--head-font);color:rgba(255,255,255,.8)}
[data-theme=statement] .hero-sub{max-width:40rem;margin:2.1rem 0 0;font:400 1.125rem/1.7 var(--body-font);color:rgba(255,255,255,.86);opacity:1}
[data-theme=statement] .hero-copy>.btns{margin-top:2rem}
.hero-foot{display:flex;align-items:center;gap:1.6rem;margin-top:2.2rem}
.hero-dots{display:flex;flex:none;gap:.55rem;margin:0;align-items:center}
.hero-dots i{display:block;width:.36rem;height:.7rem;border-radius:.2rem;background:var(--brand2);opacity:.9}
.hero-dots i.on{width:1.25rem;opacity:1}
.hero-motto{margin:0;font:700 .7rem/1.8 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.62)}
.hero-card{align-self:center;background:#fff;color:var(--text);border-top:.4rem solid var(--brand2);border-radius:.5rem;padding:2.4rem;box-shadow:0 30px 60px -30px rgba(0,0,0,.6)}
.fact{margin:0 0 1.8rem}
.fact-l{margin:0 0 .5rem;font:700 .82rem/1.4 var(--body-font);letter-spacing:.24em;text-transform:uppercase;color:var(--olive)}
.fact-v{margin:0;font:700 1.5rem/1.2 var(--head-font);color:var(--brand)}
.hero-card .btns{flex-direction:column;align-items:flex-start;gap:.8rem;margin-top:2.2rem}
[data-theme=statement] .hero .btn{min-height:3.75rem;padding:0 1.85rem;border-radius:.3rem;font:600 1.0625rem/1.2 var(--body-font);box-shadow:none}
[data-theme=statement] .hero-card .btn--solid{background:var(--brand);color:#fff}
[data-theme=statement] .hero-card .btn--ghost{background:#fff;color:var(--brand);border:2px solid var(--brand)}
[data-theme=statement] .hero-card .btn--ghost:hover{background:var(--soft)}
@media (min-width:64rem){
  .hero-card .btns{flex-direction:row;flex-wrap:wrap}
  .hero--card .hero-grid{grid-template-columns:minmax(0,1.2fr) minmax(0,.8fr);gap:5rem;align-items:center}
  [data-theme=statement] .hero .wrap{padding-top:7rem;padding-bottom:7rem}
  .hero-card{padding:2.8rem 2.6rem}
}

/* quick-action cards, if a school uses them */
[data-theme=statement] .actions{margin-top:calc(-1 * 3rem)}
[data-theme=statement] .action{border-radius:.5rem;border-left:5px solid var(--brand2);box-shadow:0 22px 44px -24px rgba(0,0,0,.55)}
[data-theme=statement] .action-ico{background:none;border-radius:0;color:var(--brand);width:2.6rem}

/* sections */
[data-theme=statement] .sec{padding:6.25rem 0}
@media (min-width:60rem){[data-theme=statement] .sec{padding:7rem 0}}
[data-theme=statement] .sec.page-head{padding-bottom:1rem}
[data-theme=statement] .sec.sec--tight{padding-top:1rem}
[data-theme=statement] .sec--quote{padding-top:0}
[data-theme=statement] .sec:has(+ .sec--quote){padding-bottom:1rem}
[data-theme=statement] .eyebrow,[data-theme=statement] .prose .eyebrow{margin:0 0 .9rem;font:700 .78rem/1.6 var(--body-font);letter-spacing:.23em;color:var(--brand2)}
[data-theme=statement] .prose .eyebrow+h1,[data-theme=statement] .prose .eyebrow+h2{margin-top:0}
[data-theme=statement] .eyebrow::before{display:none}
[data-theme=statement] .sec-head{max-width:52rem;margin-bottom:2.4rem}
[data-theme=statement] .sec-head p:not(.eyebrow){color:var(--text);font-size:1.0625rem;line-height:1.7}
[data-theme=statement] h1,[data-theme=statement] h2,[data-theme=statement] h3{text-wrap:wrap}
[data-theme=statement] .sec h1,[data-theme=statement] h2,[data-theme=statement] .prose h2{font-size:clamp(2rem,10vw,3.8rem);line-height:1.1;color:var(--brand)}
[data-theme=statement] .sec--dark h1{color:#fff}
[data-theme=statement] .prose p{max-width:44rem;color:var(--text);font-size:1.0625rem;line-height:1.7}
[data-theme=statement] .prose h3{font-size:clamp(1.45rem,6.4vw,2.2rem);line-height:1.25;color:var(--brand);margin:3.2rem 0 1.1rem}
[data-theme=statement] .prose>div>h3:first-child,[data-theme=statement] .prose h3:first-child{margin-top:0}
[data-theme=statement] .prose a{color:var(--brand);font-weight:600}
[data-theme=statement] .prose ul{max-width:none;margin:0 0 1.5rem}
[data-theme=statement] .prose ul li{margin:0;padding:.75rem 0 .75rem 1.65rem;border-bottom:1px solid var(--pink);font-size:1.0625rem;line-height:1.7}
[data-theme=statement] .prose ul li::before{content:"";position:absolute;left:.05rem;top:1.2rem;width:.55rem;height:.55rem;border-radius:.15rem;background:var(--brand2)}
[data-theme=statement] .prose ol{max-width:44rem}
[data-theme=statement] .sec--soft,[data-theme=statement] .sec--tint{background:var(--soft);border-top:1px solid var(--pink)}
[data-theme=statement] .sec--tint{border-top:0;background:#eeeae4}
[data-theme=statement] .sec--soft+.sec:not(.sec--soft){border-top:1px solid var(--pink)}

/* deep colour bands */
[data-theme=statement] .sec--dark{background:var(--rings) 0 0/92px 92px,var(--brand);color:#fff;border:0}
[data-theme=statement] .sec--dark .eyebrow{color:var(--gold-lt)}
[data-theme=statement] .sec--dark h2,[data-theme=statement] .sec--dark h3{color:#fff}
[data-theme=statement] .sec--dark .sec-head p:not(.eyebrow),[data-theme=statement] .sec--dark .prose p:not(.eyebrow){color:rgba(255,255,255,.85)}
[data-theme=statement] .sec--dark .prose a{color:var(--gold-lt)}
[data-theme=statement] .steps{display:block;max-width:none;margin:0;border-top:1px solid rgba(255,210,210,.22)}
[data-theme=statement] .steps li{display:grid;grid-template-columns:5.75rem minmax(0,1fr);gap:0;align-items:start;padding:1.9rem 0;border:0;border-bottom:1px solid rgba(255,210,210,.22);border-radius:0;background:none}
[data-theme=statement] .steps li::after,[data-theme=statement] .steps li::before{display:none!important}
[data-theme=statement] .step-n{width:auto;height:auto;margin:0;background:none;box-shadow:none;border-radius:0;display:block;font:700 2.25rem/1 var(--head-font);color:var(--gold-lt);justify-content:start}
[data-theme=statement] .step-t{font-size:1.0625rem;line-height:1.7;color:rgba(255,255,255,.85)}
[data-theme=statement] .step-t h3{display:inline;margin:0;font-family:var(--body-font);font-weight:600;font-size:inherit;line-height:inherit;letter-spacing:0;color:#fff}
[data-theme=statement] .step-t h3::after{content:" "}
[data-theme=statement] .step-t p{display:inline;margin:0;color:inherit}
[data-theme=statement] .sec:not(.sec--dark) .steps{border-top-color:var(--pink)}
[data-theme=statement] .sec:not(.sec--dark) .steps li{border-bottom-color:var(--pink)}
[data-theme=statement] .sec:not(.sec--dark) .step-t,[data-theme=statement] .sec:not(.sec--dark) .step-t p{color:var(--text)}
[data-theme=statement] .sec:not(.sec--dark) .step-t h3{color:var(--brand)}
[data-theme=statement] .sec:not(.sec--dark) .step-n{color:var(--brand2)}

/* big numbers */
[data-theme=statement] .sec--stats{padding:3.4rem 0 3.6rem;background:var(--deep);color:#fff;border-top:.4rem solid var(--brand2)}
[data-theme=statement] .stats{gap:3.2rem 1rem;text-align:center}
[data-theme=statement] .stat{border:0;padding:0}
[data-theme=statement] .stat b{display:block;font:700 2.7rem/1.1 var(--head-font);color:var(--gold-lt)}
[data-theme=statement] .stat b small{font-size:.75em;font-weight:400}
[data-theme=statement] .stat span{display:block;margin-top:1.1rem;font:600 .8rem/1.9 var(--body-font);letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.86)}
@media (min-width:48rem){[data-theme=statement] .stats{grid-template-columns:repeat(4,1fr)}[data-theme=statement] .stat b{font-size:3.6rem}}

/* quote */
.quote{margin:0;max-width:50rem;padding:.5rem 0 .5rem 1.75rem;border-left:.25rem solid var(--brand2)}
.quote blockquote{margin:0}
.quote p{margin:0;font:italic 400 1.375rem/1.4 var(--head-font);color:var(--brand)}
.quote figcaption{margin-top:1.4rem;font:700 .78rem/1.7 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--olive)}
@media (min-width:48rem){.quote p{font-size:2rem}}

/* person profile */
.profile{max-width:44rem;margin:0 auto;background:#6b1414;background:var(--brand);color:#fff;box-shadow:0 30px 60px -34px rgba(0,0,0,.5)}
.profile img{width:100%;aspect-ratio:4/5;object-fit:cover;object-position:top;background:#f4f1f8}
.profile-body{padding:2.6rem 1.75rem 2.6rem}
[data-theme=statement] .profile .eyebrow{font-size:1rem;letter-spacing:.28em;color:var(--gold-lt);margin-bottom:1.1rem}
[data-theme=statement] .profile h2,[data-theme=statement] .profile h1{color:#fff;font-size:clamp(1.75rem,7.8vw,2.8rem);margin:0 0 .6rem;line-height:1.15}
.profile-role{margin:0 0 1.4rem;font:700 .85rem/1.4 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--gold-lt)}
.profile .prose p{color:rgba(255,255,255,.88)}
[data-theme=statement] .profile .prose a{color:var(--gold-lt)}
@media (min-width:56rem){.profile{max-width:none;display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr)}.profile img{height:100%;aspect-ratio:auto}.profile-body{padding:3rem 3rem 3.2rem;align-self:center}}

/* pillar cards */
.cards--pillars{grid-template-columns:repeat(auto-fit,minmax(min(100%,19rem),1fr));gap:1.6rem}
.pillar{background:#fff;border:1px solid var(--pink);border-radius:.5rem;box-shadow:0 22px 44px -30px rgba(60,20,20,.45);border-bottom:.4rem solid var(--brand2)}
.pillar:nth-child(3n){border-bottom-color:var(--brand)}
.pillar:hover{transform:none}
.pillar .card-body{padding:2.4rem 2rem 2.4rem}
.pillar-n{margin:0 0 1.2rem!important;font:700 .74rem/1 var(--body-font);letter-spacing:.22em;text-transform:uppercase;color:var(--brand2)!important}
.pillar h3{margin:0 0 1.1rem;font-size:1.6rem;line-height:1.2;color:var(--brand)}
.pillar p:last-child{margin:0;color:var(--text);font-size:1rem;line-height:1.7}
.cards--panels{grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr));gap:1.6rem}
.panel-card{background:#fff;border:1px solid var(--pink);border-top:.4rem solid var(--brand);border-radius:.5rem;box-shadow:0 22px 44px -32px rgba(60,20,20,.35)}
.panel-card:nth-child(even){border-top-color:var(--brand2)}
.panel-card:hover{transform:none}
.panel-card .card-body{padding:2.4rem 2.5rem 2.6rem}
.panel-card h3{margin:0 0 1.2rem;font-size:1.75rem;line-height:1.2;color:var(--brand)}
.panel-card p{margin:0;font-size:1.0625rem;line-height:1.7;color:var(--text)}
[data-theme=statement] .card--plain{border-left:5px solid var(--brand2)}
[data-theme=statement] .card--plain::before{display:none}
[data-theme=statement] .news-item{border-radius:.5rem;border-top:5px solid var(--brand2)}

/* call to action band */
[data-theme=statement] .cta{background:var(--rings) 0 0/92px 92px,var(--deep);color:#fff;padding:clamp(4.5rem,8vw,6rem) 0;text-align:left}
[data-theme=statement] .cta h2{color:#fff;font-size:clamp(2rem,9.7vw,3.7rem);max-width:20ch}
[data-theme=statement] .cta p{margin-left:0;margin-right:0;color:rgba(255,255,255,.86)}
[data-theme=statement] .cta .btns{justify-content:flex-start;margin-left:0;max-width:none}
[data-theme=statement] .cta .btn{flex:0 0 auto;min-height:3.75rem;padding:0 1.85rem;border-radius:.3rem;font:600 1.0625rem/1.2 var(--body-font)}
[data-theme=statement] .cta .btn--ghost{border:2px solid rgba(255,255,255,.6)}
[data-theme=statement] .faq{max-width:50rem}
[data-theme=statement] .faq details{border:1px solid var(--pink);border-left:5px solid var(--brand2);border-radius:.4rem;background:#fff;margin-bottom:.7rem;padding:0 1.25rem}

/* footer: contact box, two buttons, small print */
.ftr--statement{background:var(--rings) 0 0/92px 92px,var(--deep);color:rgba(255,255,255,.92);padding:3.1rem 0 2.6rem;border:0}
.ftr--statement a{color:var(--gold-lt);font-weight:600;text-decoration:none}
.ftr-main{display:grid;gap:2.2rem}
.ftr-box{border:1px solid rgba(255,210,210,.3);border-radius:.5rem;padding:2.2rem 1.7rem 1.8rem}
.ftr--statement h2{margin:0 0 1.2rem;font:700 1.05rem/1.2 var(--body-font);letter-spacing:.3em;text-transform:uppercase;color:var(--gold-lt)}
.ftr-box p{margin:0;font-size:1.1rem;line-height:1.55}
.ftr--statement .social{display:flex;flex-wrap:wrap;gap:.4rem 1rem;margin-top:1.2rem}
.ftr--statement .social a{color:var(--gold-lt)}
.ftr-cta .btns{flex-direction:column;align-items:flex-start;gap:.8rem}
.ftr--statement .btn{min-height:3.75rem;padding:0 1.85rem;border-radius:.3rem;font:600 1.0625rem/1.2 var(--body-font);text-decoration:none}
.ftr--statement .btn--solid{background:var(--brand2);color:var(--deep);font-weight:700}
.ftr--statement .btn--ghost{color:#fff;border:2px solid rgba(255,255,255,.6)}
.ftr-links{display:flex;flex-wrap:wrap;gap:.1rem 1.4rem;margin:2.2rem 0 0;padding:0;list-style:none;line-height:1.5}
.ftr--statement .ftr-links a{color:rgba(255,255,255,.7);font-weight:500;font-size:.95rem;padding:.2rem 0}
.ftr--statement .legal{margin-top:3rem;padding-top:2rem;border-top:1px solid rgba(255,210,210,.22);align-items:flex-start;text-align:left;gap:.9rem;font-size:1.05rem}
.ftr--statement .legal p{opacity:1;color:rgba(255,255,255,.62);line-height:1.7}
.ftr--statement .legal a{color:inherit;font-weight:400;text-decoration:underline}
@media (min-width:56rem){.ftr-main{grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);align-items:start;gap:3rem}.ftr-box{padding:2.6rem 2.4rem}.ftr--statement .legal{flex-direction:row;justify-content:space-between}}

/* sign-in and apply pages sit on a soft band like the original's sign-up page */
[data-theme=statement] .pagehero{background:var(--soft);color:var(--text);padding:5rem 0 2.2rem;border:0;overflow:visible}
[data-theme=statement] .pagehero::before,[data-theme=statement] .pagehero::after{display:none}
[data-theme=statement] .pagehero h1{color:var(--brand);max-width:20ch;font-size:clamp(2.2rem,9.7vw,3.7rem)}
[data-theme=statement] .pagehero .hero-tag{display:block;padding:0;border:0;background:none;color:var(--brand2);font:700 .8rem/1.6 var(--body-font);letter-spacing:.24em;text-transform:uppercase;-webkit-backdrop-filter:none;backdrop-filter:none}
[data-theme=statement] .pagehero .hero-tag i{display:none}
[data-theme=statement] .pagehero p{opacity:1;color:var(--text)}
[data-theme=statement] .formwrap{margin-top:0;background:var(--soft);padding-top:1rem}
[data-theme=statement] .panel{border-radius:.5rem;border:1px solid var(--pink);border-top:.4rem solid var(--brand2);box-shadow:none;background:#fff}
[data-theme=statement] .panel--soft{background:var(--bg)}
[data-theme=statement] .field>span{color:var(--brand);font-weight:600}
[data-theme=statement] .field input,[data-theme=statement] .field select,[data-theme=statement] .field textarea{background:#fff;border:1px solid #e6d3d1;border-left:.3rem solid var(--brand2);border-radius:.4rem;min-height:3.4rem}
[data-theme=statement] .btn--solid{border-radius:.3rem}
@endverbatim
