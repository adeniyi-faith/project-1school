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

/* ================= STATEMENT: deep bands, huge serif headlines, pillars and numbered commitments ================= */
[data-theme=statement]{--hdr-h:5.1rem}
[data-theme=statement] body,body[data-theme=statement]{overflow-x:clip}
[data-theme=statement] h1,[data-theme=statement] h2,[data-theme=statement] h3{font-weight:700;letter-spacing:-.015em}
[data-theme=statement] .hdr{background:var(--bg);box-shadow:none;border-bottom:4px solid var(--brand)}
[data-theme=statement] .hdr-in{min-height:5.1rem;padding:.5rem 0}
[data-theme=statement] .brand span{font:700 1.5rem/1.05 var(--head-font);letter-spacing:-.01em}
[data-theme=statement] .brand small{color:var(--text);opacity:.65}
[data-theme=statement] .menu a{font-family:var(--head-font);font-weight:700;white-space:nowrap}
@media (min-width:60rem){
  [data-theme=statement] .menu a{font:700 .74rem/1 var(--body-font);letter-spacing:.1em;text-transform:uppercase;border-radius:0;padding:.7rem .6rem;border-bottom:3px solid transparent;white-space:nowrap}
  [data-theme=statement] .brand{flex:1 1 auto}
  [data-theme=statement] .menu{flex:none}
  [data-theme=statement] .menu a:hover{background:none;border-bottom-color:var(--brand2)}
  [data-theme=statement] .menu a[aria-current]{background:none;border-bottom-color:var(--brand)}
  [data-theme=statement] .menu .btn{border-radius:.4rem;border-bottom:0;letter-spacing:.06em;padding:.65rem 1.3rem}
}
.hero--statement{min-height:min(88svh,46rem);background:var(--deep);color:#fff}
.hero--statement::before{content:"";position:absolute;inset:0;z-index:-1;opacity:.55;background-image:radial-gradient(circle at center,transparent 0 .62rem,color-mix(in srgb,var(--brand2) 28%,transparent) .66rem .72rem,transparent .76rem);background-size:11.5rem 11.5rem;background-position:1rem 1rem;-webkit-mask-image:none;mask-image:none}
.hero--statement.hero--photo::before{opacity:.25}
.hero--statement.hero--photo .hero-img{opacity:.28;mix-blend-mode:luminosity}
.hero--statement::after{display:none}
[data-theme=statement] .hero--statement{background:linear-gradient(160deg,var(--brand),var(--deep))}
[data-theme=statement] .hero--statement .wrap{padding-top:clamp(3rem,8vw,5.5rem);padding-bottom:clamp(5rem,12vw,8rem)}
.hero-kicker{margin:0 0 1.2rem;font:700 .82rem/1.5 var(--body-font);letter-spacing:.22em;text-transform:uppercase;color:color-mix(in srgb,var(--brand2) 78%,#fff)}
[data-theme=statement] .hero h1,[data-theme=statement] .hero h2{max-width:15ch;font-size:clamp(2.8rem,8.6vw,6rem);font-weight:700;line-height:1.02;letter-spacing:-.025em;text-shadow:none}
[data-theme=statement] .hero h1 em,[data-theme=statement] .hero h2 em{color:color-mix(in srgb,var(--brand2) 80%,#fff);font-weight:500}
[data-theme=statement] .hero-sub{max-width:38rem;font-size:clamp(1.1rem,2.4vw,1.35rem);opacity:.9}
[data-theme=statement] .hero .btn--solid{background:var(--brand2);color:var(--brand2-ink);box-shadow:none}
[data-theme=statement] .hero .btn--ghost{border-color:rgba(255,255,255,.55)}
.hero-dots{display:flex;gap:.45rem;margin:3rem 0 0}
.hero-dots i{display:block;width:.55rem;height:.9rem;border-radius:.3rem;background:color-mix(in srgb,var(--brand2) 70%,transparent)}
.hero-dots i.on{width:2.4rem;background:var(--brand2)}
[data-theme=statement] .actions{margin-top:calc(-1 * 3rem)}
[data-theme=statement] .action{border-radius:.5rem;border-left:5px solid var(--brand2);box-shadow:0 22px 44px -24px rgba(0,0,0,.55)}
[data-theme=statement] .action-ico{background:none;border-radius:0;color:var(--brand);width:2.6rem}
[data-theme=statement] .sec{padding:clamp(3.5rem,9vw,6.5rem) 0}
[data-theme=statement] .eyebrow{color:var(--brand2);letter-spacing:.22em}
[data-theme=statement] .eyebrow::before{display:none}
[data-theme=statement] .sec-head h2,[data-theme=statement] .prose h2{color:var(--brand)}
[data-theme=statement] .sec.page-head{padding-bottom:1rem}
[data-theme=statement] .sec.sec--tight{padding-top:1rem}
[data-theme=statement] .sec--soft{background:var(--soft);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.cards--pillars{grid-template-columns:repeat(auto-fit,minmax(min(100%,19rem),1fr))}
.pillar{border:1px solid color-mix(in srgb,var(--brand) 14%,transparent);border-radius:.5rem;box-shadow:0 18px 36px -28px rgba(0,0,0,.4);border-bottom:6px solid var(--brand2)}
.pillar:hover{transform:none;box-shadow:0 24px 44px -26px rgba(0,0,0,.5)}
.pillar .card-body{padding:2rem 1.75rem 2rem}
.pillar-n{margin:0 0 .8rem!important;font:700 .8rem/1 var(--body-font);letter-spacing:.24em;text-transform:uppercase;color:var(--brand2)!important}
.pillar-n::before{content:"Pillar ";}
.pillar h3{font-size:1.9rem;color:var(--brand)}
[data-theme=statement] .stats{background:var(--deep);color:var(--deep-ink);margin:0 calc(50% - 50vw);padding:clamp(2.5rem,6vw,4rem) calc(50vw - 50%);border-top:6px solid var(--brand2)}
[data-theme=statement] .stat{border:0}
[data-theme=statement] .stat b{color:color-mix(in srgb,var(--brand2) 80%,#fff);font-weight:700}
[data-theme=statement] .stat span{color:inherit;opacity:.85;text-transform:uppercase;letter-spacing:.14em;font-size:.82rem;font-weight:600}
[data-theme=statement] .sec:has(> .wrap > .stats){padding:0}
[data-theme=statement] .sec:has(.steps){background:var(--brand);color:#fff}
[data-theme=statement] .sec:has(.steps) .eyebrow{color:color-mix(in srgb,var(--brand2) 80%,#fff)}
[data-theme=statement] .sec:has(.steps) h2,[data-theme=statement] .sec:has(.steps) h3{color:#fff}
[data-theme=statement] .sec:has(.steps) .sec-head p{color:rgba(255,255,255,.8)}
[data-theme=statement] .steps{display:block;max-width:56rem;border-top:1px solid rgba(255,255,255,.25)}
[data-theme=statement] .steps li{display:grid;grid-template-columns:4.5rem 1fr;gap:0 1.5rem;align-items:start;padding:1.6rem 0;border:0;border-bottom:1px solid rgba(255,255,255,.25);border-radius:0;background:none}
[data-theme=statement] .steps li::after{display:none!important}
[data-theme=statement] .step-n{grid-row:span 2;width:auto;height:auto;margin:0;background:none;box-shadow:none;border-radius:0;font:700 3.2rem/1 var(--head-font);color:color-mix(in srgb,var(--brand2) 80%,#fff);justify-content:start}
[data-theme=statement] .steps h3{margin:0 0 .3rem;font-size:1.3rem}
[data-theme=statement] .steps p{color:rgba(255,255,255,.85)}
[data-theme=statement] .card--plain{border-left:5px solid var(--brand2)}
[data-theme=statement] .card--plain::before{display:none}
[data-theme=statement] .news-item{border-radius:.5rem;border-top:5px solid var(--brand2)}
[data-theme=statement] .cta{background:var(--deep);color:var(--deep-ink);padding:clamp(3.5rem,8vw,6rem) 0;text-align:left}
[data-theme=statement] .cta h2{color:#fff;font-size:clamp(2rem,5.5vw,3.6rem);max-width:20ch}
[data-theme=statement] .cta p{margin-left:0;margin-right:0}
[data-theme=statement] .cta .btns{justify-content:flex-start;margin-left:0}
[data-theme=statement] .cta .btn--ghost{border-color:rgba(255,255,255,.5)}
[data-theme=statement] .faq{max-width:50rem}
[data-theme=statement] .faq details{border:1px solid var(--line);border-left:5px solid var(--brand2);border-radius:.4rem;background:var(--bg);margin-bottom:.7rem;padding:0 1.25rem}
[data-theme=statement] .ftr{background:var(--deep);border-top:6px solid var(--brand2)}
[data-theme=statement] .ftr h2{font-family:var(--body-font);font-size:.8rem;letter-spacing:.2em;text-transform:uppercase;color:color-mix(in srgb,var(--brand2) 80%,#fff)}
[data-theme=statement] .pagehero{background:linear-gradient(160deg,var(--brand),var(--deep));border-bottom:6px solid var(--brand2)}
[data-theme=statement] .pagehero::after{display:none}
[data-theme=statement] .panel{border-radius:.5rem;border-top:5px solid var(--brand2)}
@endverbatim
