@verbatim
/* ============ LEAF: white bar, left slide-in menu, cream banner with leaf-shaped photos and a wavy edge, brush-underlined headlines, tinted cards, gold accordion ============ */
[data-theme=leaf]{--hdr-h:5rem;--leaf:clamp(4.5rem,24vw,9rem);--leaf-lg:clamp(3.2rem,13vw,6.5rem);--ease:cubic-bezier(.2,.8,.2,1);--tone-a:color-mix(in srgb,var(--brand) 6%,#fff);--tone-b:color-mix(in srgb,var(--brand2) 15%,#fff);--tone-c:color-mix(in srgb,var(--text) 5%,#fff);--gray:#5d5d66;--brush:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 216 28'%3E%3Cpath d='M2 19C48 7 128 2 214 13 132 9 66 15 3 27Z'/%3E%3C/svg%3E")}
[data-theme=leaf],[data-theme=leaf] body{overflow-x:clip}
body[data-theme=leaf]{font-size:1.0625rem;line-height:1.8;color:var(--text)}
[data-theme=leaf] h1,[data-theme=leaf] h2,[data-theme=leaf] h3{font-family:var(--head-font);font-weight:700;color:var(--text);letter-spacing:-.005em;line-height:1.12;text-wrap:balance}
[data-theme=leaf] h1{font-size:clamp(2.45rem,9.4vw,4.6rem);line-height:1.08}
[data-theme=leaf] h2{font-size:clamp(2rem,6.4vw,3.2rem)}
[data-theme=leaf] h3{font-size:clamp(1.35rem,3.4vw,1.65rem);font-weight:600;letter-spacing:.005em}
[data-theme=leaf] em{font-style:normal;color:var(--brand-t)}
[data-theme=leaf] .sec--dark em{color:var(--brand2-d)}
[data-theme=leaf] .sec{padding:clamp(3.5rem,9vw,6.5rem) 0}
[data-theme=leaf] .sec--soft{background:var(--soft)}
[data-theme=leaf] .sec--dark{background:var(--deep);color:#fff}
[data-theme=leaf] .sec--dark h1,[data-theme=leaf] .sec--dark h2,[data-theme=leaf] .sec--dark h3{color:#fff}
[data-theme=leaf] .sec-head{margin-bottom:clamp(2rem,5vw,3.25rem)}
[data-theme=leaf] .sec-head p:not(.eyebrow){font-size:1.1rem;line-height:1.8;color:var(--gray)}
[data-theme=leaf] .sec-head.center{max-width:42rem}
[data-theme=leaf] .prose p,[data-theme=leaf] .prose li{font-size:1.075rem;line-height:1.9;color:var(--gray)}
[data-theme=leaf] .sec--dark .prose p,[data-theme=leaf] .sec--dark .prose li{color:rgba(255,255,255,.88)}
[data-theme=leaf] .prose h3{margin:2rem 0 .6rem}
[data-theme=leaf] .center.prose p{font-size:clamp(1.15rem,3.2vw,1.45rem);line-height:1.75}
[data-theme=leaf] .eyebrow,[data-theme=leaf] .prose p.eyebrow{margin:0 0 1rem;font:500 .84rem/1.3 var(--body-font);letter-spacing:.2em;color:var(--brand-t)}
[data-theme=leaf] .eyebrow::before{display:none}
[data-theme=leaf] .eyebrow+h1,[data-theme=leaf] .eyebrow+h2,[data-theme=leaf] .eyebrow+h3{margin-top:0}
[data-theme=leaf] .prose blockquote{margin:1.75rem 0;padding:.25rem 0 .25rem 1.4rem;border-left:4px solid var(--brand2);font-style:normal;font-weight:500;color:var(--text)}
[data-theme=leaf] .more{color:var(--brand-t)}
[data-theme=leaf] a:focus-visible,[data-theme=leaf] button:focus-visible,[data-theme=leaf] summary:focus-visible{outline:3px solid var(--brand2-t);outline-offset:3px;border-radius:.4rem}

/* the brush stroke under big headlines */
[data-theme=leaf] .sec :is(h1,h2):not(.sr)::after{content:"";display:block;width:clamp(5.5rem,22vw,7.5rem);height:.8rem;margin:1rem 0 0;background:var(--brand2);-webkit-mask:var(--brush) center/100% 100% no-repeat;mask:var(--brush) center/100% 100% no-repeat}
[data-theme=leaf] :is(.center,.sec-head.center) :is(h1,h2)::after,[data-theme=leaf] h2.center::after{margin-inline:auto}
[data-theme=leaf] .sec--dark :is(h1,h2)::after{background:var(--brand2-d)}
[data-theme=leaf] .sec-head :is(h1,h2)+p,[data-theme=leaf] .prose :is(h1,h2)+p{margin-top:1.6rem}

/* prose lists become gold check lists */
[data-theme=leaf] .prose ul{list-style:none;margin:1.25rem 0;padding:0}
[data-theme=leaf] .prose ul li{position:relative;margin:.6rem 0;padding-left:2.4rem;color:var(--text)}
[data-theme=leaf] .prose ul li::before{content:"";position:absolute;left:0;top:.5em;width:1.5rem;height:1.5rem;background:var(--brand2);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='m3 12.5 5.2 5.3L21 5.2' fill='none' stroke='%23000' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='m3 12.5 5.2 5.3L21 5.2' fill='none' stroke='%23000' stroke-width='3.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat}

/* photos next to words take the leaf shape */
[data-theme=leaf] .split{gap:clamp(2rem,6vw,4.5rem)}
[data-theme=leaf] .split img{aspect-ratio:5/6;border-radius:0 var(--leaf-lg) 0 var(--leaf-lg);box-shadow:0 34px 60px -38px rgba(20,20,40,.5)}
[data-theme=leaf] .split--left img{border-radius:var(--leaf-lg) 0 var(--leaf-lg) 0}

/* buttons: square-ish, gold, with a little arrow */
[data-theme=leaf] .btn{min-height:3.4rem;padding:0 1.9rem;border:2px solid transparent;border-radius:var(--btn-radius);font:500 1.02rem/1 var(--body-font);letter-spacing:0;text-transform:none;white-space:nowrap;gap:.7rem;box-shadow:none}
[data-theme=leaf] a.btn::after{content:"";flex:none;width:1.1em;height:1.1em;background:currentColor;-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4 12h15m-6-6 6 6-6 6' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M4 12h15m-6-6 6 6-6 6' fill='none' stroke='%23000' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;transition:transform .25s var(--ease)}
[data-theme=leaf] a.btn:hover::after{transform:translateX(4px)}
[data-theme=leaf] .btn--solid{background:var(--fill);color:var(--fill-ink);border-color:var(--fill)}
[data-theme=leaf] .btn--solid:hover{transform:translateY(-2px);box-shadow:0 16px 28px -16px var(--fill)}
[data-theme=leaf] .btn--ghost{background:transparent;color:var(--text);border-color:var(--text)}
[data-theme=leaf] .btn--ghost:hover{background:var(--text);color:#fff;transform:translateY(-2px)}
[data-theme=leaf] .btns{gap:.8rem}
[data-theme=leaf] button.btn::after,[data-theme=leaf] .menu .btn::after{display:none}

/* ----- header ----- */
[data-theme=leaf] .hdr{background:#fff;color:var(--text);border-bottom:0;box-shadow:0 10px 30px -20px rgba(20,20,40,.35)}
[data-theme=leaf] .hdr-in{min-height:var(--hdr-h);gap:.75rem;flex-wrap:nowrap}
[data-theme=leaf] .brand{flex:1 1 auto;gap:.75rem;color:var(--text)}
[data-theme=leaf] .brand img{height:3.7rem;max-width:4.4rem;flex:none}
[data-theme=leaf] .brand span{display:block;font:700 clamp(.9rem,3.8vw,1.08rem)/1.2 var(--head-font);letter-spacing:.01em;color:var(--text);text-wrap:balance}
@media (max-width:24rem){[data-theme=leaf] .brand img{height:3.1rem;max-width:3.6rem}[data-theme=leaf] .brand span{font-size:.82rem}}
[data-theme=leaf] .lf-tools{display:flex;align-items:center;flex:none}
[data-theme=leaf] .sbtn,[data-theme=leaf] .lf-burger{width:3rem;height:3rem;color:var(--text)}
[data-theme=leaf] .lf-burger{padding:.65rem .5rem;gap:.34rem}
[data-theme=leaf] .lf-burger i{height:3.5px;border-radius:3px}
[data-theme=leaf] .hdr .sbar{background:#fff;color:var(--text)}

/* ----- the menu: a drawer from the left on phones and tablets, one tidy line on laptops ----- */
[data-theme=leaf] .menu-list{list-style:none;margin:0;padding:0}
[data-theme=leaf] .lf-scrim{display:block;position:fixed;inset:0;z-index:60;width:100%;height:100%;padding:0;border:0;background:rgba(15,15,30,.55);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);opacity:0;visibility:hidden;transition:opacity .3s,visibility 0s .3s}
body.menu-open[data-theme=leaf] .lf-scrim{opacity:1;visibility:visible;transition:opacity .3s}
@media (max-width:71.99rem){
@endverbatim
@include('website.styles-leaf-drawer', ['p' => '[data-theme=leaf]'])
@verbatim
}
@media (min-width:72rem){
  .lf-drawer-top,.lf-drawer-foot,[data-theme=leaf] .lf-scrim{display:none}
  [data-theme=leaf] .menu{display:flex;align-items:center;position:static;padding:0;background:none;color:inherit;overflow:visible;transform:none;visibility:visible;width:auto;flex:none;order:2}
  [data-theme=leaf] .lf-tools{order:3}
  [data-theme=leaf] .brand{order:1}
  [data-theme=leaf] .menu-list{display:flex;align-items:center;gap:.1rem}
  [data-theme=leaf] .menu-item{position:relative;display:flex;align-items:center}
  [data-theme=leaf] .menu .menu-item>a:not(.btn){display:block;padding:.8rem .8rem;border:0;font:500 .98rem/1 var(--body-font);color:var(--text);white-space:nowrap;position:relative;transition:color .2s}
  [data-theme=leaf] .menu .menu-item>a:not(.btn)::before{content:"";position:absolute;left:.8rem;right:.8rem;bottom:.35rem;height:3px;border-radius:3px;background:var(--brand2);transform:scaleX(0);transform-origin:left;transition:transform .3s var(--ease)}
  [data-theme=leaf] .menu .menu-item>a:not(.btn):hover::before,[data-theme=leaf] .menu .menu-item>a[aria-current]::before{transform:none}
  [data-theme=leaf] .menu .menu-item>a:not(.btn):hover,[data-theme=leaf] .menu .menu-item>a[aria-current]{color:var(--text);background:none;opacity:1}
  [data-theme=leaf] .lf-apply{margin-left:.5rem}
  [data-theme=leaf] .menu .lf-apply .btn{display:inline-flex;align-items:center;justify-content:center;min-height:2.8rem;margin:0;padding:0 1.4rem;border:2px solid var(--fill);background:var(--fill);color:var(--fill-ink);font:500 .95rem/1 var(--body-font)}
  [data-theme=leaf] .menu .menu-sub{display:none;position:absolute;top:100%;left:0;z-index:80;min-width:16rem;margin:0;padding:.6rem 0;border:0;border-radius:var(--radius);background:#fff;box-shadow:0 26px 50px -20px rgba(20,20,40,.4)}
  [data-theme=leaf] .menu-item:hover>.menu-sub,[data-theme=leaf] .menu-item:focus-within>.menu-sub{display:block}
  [data-theme=leaf] .menu .menu-item>.menu-sub a{padding:.75rem 1.3rem;color:var(--text);font:500 .95rem/1.3 var(--body-font);white-space:nowrap}
  [data-theme=leaf] .menu .menu-item>.menu-sub a:hover{background:var(--soft);color:var(--brand-t)}
}

/* ----- the banner ----- */
[data-theme=leaf] .hero--leaf{min-height:0;text-shadow:none;position:relative;overflow:hidden;background:var(--soft);padding:clamp(3rem,9vw,5.5rem) 0 0;text-align:center;color:var(--text)}
[data-theme=leaf] .hero--leaf::before{content:"";position:absolute;inset:-20% -30% auto auto;width:60rem;max-width:140vw;aspect-ratio:1;border-radius:50%;background:radial-gradient(closest-side,color-mix(in srgb,var(--brand2) 22%,transparent),transparent);pointer-events:none}
[data-theme=leaf] .hero--leaf :is(h1,h2,p,.eyebrow){text-shadow:none}
[data-theme=leaf] .lf-hero-in{position:relative;display:grid;gap:clamp(2.25rem,6vw,3.5rem)}
[data-theme=leaf] .lf-hero-text{max-width:46rem;margin:0 auto}
[data-theme=leaf] .hero--leaf h1,[data-theme=leaf] .hero--leaf h2{color:var(--text);font-size:clamp(2.5rem,10.4vw,4.9rem)}
[data-theme=leaf] .hero--leaf .hero-sub{margin:1.5rem auto 0;max-width:36rem;font-size:clamp(1.08rem,3.6vw,1.3rem);line-height:1.8;color:var(--text)}
[data-theme=leaf] .hero--leaf .btns{justify-content:center;margin-top:2rem}
[data-theme=leaf] .hero--leaf .btn--solid{background:var(--fill);color:var(--fill-ink);box-shadow:0 18px 30px -18px var(--fill)}
[data-theme=leaf] .lf-leaves{display:grid;grid-template-columns:1fr 1fr;gap:clamp(1.25rem,5vw,3rem);align-items:start;padding-bottom:clamp(5rem,14vw,8rem)}
[data-theme=leaf] .lf-leaf{margin:0;overflow:hidden;background:color-mix(in srgb,var(--brand2) 30%,#fff)}
[data-theme=leaf] .lf-leaf img{display:block;width:100%;height:100%;object-fit:cover}
[data-theme=leaf] .lf-leaf--a{aspect-ratio:9/10;border-radius:0 var(--leaf) 0 var(--leaf)}
[data-theme=leaf] .lf-leaf--b{aspect-ratio:2/3;border-radius:var(--leaf) 0 var(--leaf) 0}
[data-theme=leaf] .lf-wave{position:absolute;left:0;right:0;bottom:-1px;width:100%;height:clamp(3rem,9vw,6.5rem);fill:var(--bg);display:block}
@media (min-width:60rem){
  [data-theme=leaf] .hero--leaf{text-align:left;padding-top:clamp(4rem,7vw,6rem)}
  [data-theme=leaf] .lf-hero-in{grid-template-columns:1fr 1fr;align-items:center;gap:clamp(2.5rem,6vw,5rem)}
  [data-theme=leaf] .lf-hero-text{margin:0;max-width:none}
  [data-theme=leaf] .hero--leaf .hero-sub{margin-left:0}
  [data-theme=leaf] .hero--leaf .btns{justify-content:flex-start}
  [data-theme=leaf] .lf-leaves{--leaf:clamp(5rem,10vw,9rem);padding-bottom:clamp(6rem,10vw,9rem);max-width:40rem;margin-left:auto}
  [data-theme=leaf] .hero--leaf h1,[data-theme=leaf] .hero--leaf h2{font-size:clamp(2.8rem,5.2vw,4.4rem)}
  [data-theme=leaf] .lf-leaf--b{margin-top:2.5rem}
}
@media (prefers-reduced-motion:no-preference){
  [data-theme=leaf] .lf-hero-text>*{animation:lf-rise .8s var(--ease) both}
  [data-theme=leaf] .lf-hero-text>*:nth-child(2){animation-delay:.08s}
  [data-theme=leaf] .lf-hero-text>*:nth-child(3){animation-delay:.16s}
  [data-theme=leaf] .lf-hero-text>*:nth-child(4){animation-delay:.24s}
  [data-theme=leaf] .lf-leaf--a{animation:lf-rise 1s .18s var(--ease) both}
  [data-theme=leaf] .lf-leaf--b{animation:lf-rise 1s .32s var(--ease) both}
  @keyframes lf-rise{from{opacity:0;transform:translateY(1.4rem)}to{opacity:1;transform:none}}
}

/* ----- quick actions sit just under the wave ----- */
[data-theme=leaf] .actions{margin-top:calc(-1 * clamp(1.5rem,5vw,3rem))}
[data-theme=leaf] .action{border-radius:var(--radius-lg);box-shadow:0 24px 50px -28px rgba(20,20,40,.45),0 0 0 1px var(--line)}
[data-theme=leaf] .action-ico,[data-theme=leaf] .card-ico{border-radius:50%;background:var(--tone-a);color:var(--brand-t)}
[data-theme=leaf] .action-text b{font-family:var(--head-font);font-weight:600;font-size:1.15rem}

/* ----- feature cards: tinted cards with a round icon, or photo cards with the icon sitting on the picture's edge ----- */
[data-theme=leaf] .lf-cards{display:grid;gap:clamp(1.25rem,3vw,2rem);grid-template-columns:minmax(0,1fr)}
[data-theme=leaf] .lf-card{--tint:var(--tone-a);--ic:var(--brand-t);--ic-bg:color-mix(in srgb,var(--brand) 14%,#fff);display:flex;flex-direction:column;align-items:center;text-align:center;padding:clamp(2rem,5vw,2.75rem) clamp(1.5rem,4vw,2.25rem);border-radius:var(--radius-lg);background:var(--tint);transition:transform .35s var(--ease),box-shadow .35s var(--ease)}
[data-theme=leaf] .lf-card--b{--tint:var(--tone-b);--ic:color-mix(in srgb,var(--brand2-t) 75%,#000);--ic-bg:color-mix(in srgb,var(--brand2) 32%,#fff)}
[data-theme=leaf] .lf-card--c{--tint:var(--tone-c);--ic:var(--text);--ic-bg:color-mix(in srgb,var(--text) 10%,#fff)}
[data-theme=leaf] .lf-card:hover{transform:translateY(-6px);box-shadow:0 30px 50px -32px rgba(20,20,40,.5)}
[data-theme=leaf] .lf-ico{display:grid;place-items:center;flex:none;width:5.2rem;height:5.2rem;margin-bottom:1.35rem;border-radius:50%;background:var(--ic-bg);color:var(--ic)}
[data-theme=leaf] .lf-ico .ico{width:2.3rem;height:2.3rem}
[data-theme=leaf] .lf-ico b{font:700 1.7rem/1 var(--head-font)}
[data-theme=leaf] .lf-card h3{margin:0 0 .7rem}
[data-theme=leaf] .lf-card p{margin:0;color:var(--text);font-size:1.02rem;line-height:1.85}
[data-theme=leaf] .lf-more{display:inline-flex;align-items:center;justify-content:center;margin-top:1.6rem;min-height:3rem;padding:0 1.5rem;border-radius:.55rem;background:var(--ic-bg);color:var(--ic);font:500 1rem/1 var(--body-font);text-decoration:none;transition:background .2s,color .2s}
[data-theme=leaf] .lf-more:hover{background:var(--ic);color:#fff}
[data-theme=leaf] .lf-card--photo{padding:0 0 clamp(1.75rem,4vw,2.25rem);background:#fff;overflow:hidden;box-shadow:0 20px 44px -30px rgba(20,20,40,.45)}
[data-theme=leaf] .lf-card--photo .lf-card-pic{align-self:stretch;aspect-ratio:3/2;overflow:hidden}
[data-theme=leaf] .lf-card--photo .lf-card-pic img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .8s var(--ease)}
[data-theme=leaf] .lf-card--photo:hover .lf-card-pic img{transform:scale(1.05)}
[data-theme=leaf] .lf-card--photo .lf-ico{margin-top:-2.6rem;position:relative;background:var(--brand);color:var(--brand-ink);box-shadow:0 0 0 .4rem #fff}
[data-theme=leaf] .lf-card--photo h3,[data-theme=leaf] .lf-card--photo p,[data-theme=leaf] .lf-card--photo .lf-more{margin-left:clamp(1.25rem,4vw,2rem);margin-right:clamp(1.25rem,4vw,2rem)}
[data-theme=leaf] .lf-card--photo .lf-more{margin-bottom:0}
@media (min-width:44rem){[data-theme=leaf] .lf-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (min-width:68rem){[data-theme=leaf] .lf-cards{grid-template-columns:repeat(3,minmax(0,1fr))}}

/* ----- other shared sections ----- */
[data-theme=leaf] .card{border-radius:var(--radius-lg);box-shadow:0 20px 44px -34px rgba(20,20,40,.45)}
[data-theme=leaf] .steps li{padding:2rem 1.75rem;border-radius:var(--radius-lg);background:var(--soft)}
[data-theme=leaf] .steps h3{font-size:1.3rem}
[data-theme=leaf] .steps p{color:var(--gray)}
[data-theme=leaf] .sec--soft .steps li{background:#fff}
[data-theme=leaf] .step-n{display:grid;place-items:center;width:3.2rem;height:3.2rem;margin-bottom:1rem;border-radius:50%;background:var(--fill);color:var(--fill-ink);font:700 1.3rem/1 var(--head-font)}
[data-theme=leaf] .sec--stats{background:var(--soft)}
[data-theme=leaf] .stat b{font:700 clamp(2.4rem,7vw,3.6rem)/1 var(--head-font);color:var(--brand-t)}
[data-theme=leaf] .stat span{font-weight:500;color:var(--gray)}

/* the questions and answers: a white card per question, the open one wears a gold bar */
[data-theme=leaf] .faq{display:grid;gap:.9rem;max-width:52rem}
[data-theme=leaf] .faq details{border:1px solid var(--line);border-radius:var(--radius);background:#fff;overflow:hidden;box-shadow:0 14px 30px -26px rgba(20,20,40,.5)}
[data-theme=leaf] .faq summary{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.4rem;font:600 clamp(1.1rem,3.4vw,1.3rem)/1.35 var(--head-font);letter-spacing:.005em;color:var(--text);transition:background .25s,color .25s}
[data-theme=leaf] .faq summary::after{content:"";position:static;flex:none;width:1.4rem;height:1.4rem;background:currentColor;font-size:0;transition:transform .3s var(--ease);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='m6 9 6 6 6-6' fill='none' stroke='%23000' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='m6 9 6 6 6-6' fill='none' stroke='%23000' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center/contain no-repeat}
[data-theme=leaf] .faq details[open] summary{background:var(--fill);color:var(--fill-ink)}
[data-theme=leaf] .faq details[open] summary::after{transform:rotate(180deg)}
[data-theme=leaf] .faq details p{padding:1.3rem 1.4rem 1.4rem;color:var(--gray);line-height:1.9}

/* quotes and testimonials: a gold quote mark, big calm words, a round photo */
[data-theme=leaf] .sec--testi{background:linear-gradient(177deg,var(--bg) 0 3.4rem,var(--soft) calc(3.4rem + 1px));padding-top:clamp(5rem,12vw,8rem)}
[data-theme=leaf] .sec--testi.sec--soft,[data-theme=leaf] .sec--testi.sec--dark{background:var(--soft)}
[data-theme=leaf] .sec--testi.sec--dark{background:var(--deep)}
[data-theme=leaf] .tcard{padding:0;background:none;border:0;border-radius:0;gap:1.75rem}
[data-theme=leaf] .tcard blockquote{font:400 clamp(1.1rem,3.4vw,1.4rem)/1.9 var(--body-font);color:var(--text)}
[data-theme=leaf] .tcard blockquote::before{content:"";display:block;width:3.2rem;height:2.6rem;margin-bottom:1.25rem;background:var(--brand2);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 26'%3E%3Cpath d='M0 26V15C0 6.5 4.6 1.2 12.5 0l1.2 3.7C9.4 5 7.2 7.6 7 11.6h6.2V26Zm18.8 0V15C18.8 6.5 23.4 1.2 31.3 0l1.2 3.7c-4.3 1.3-6.5 3.9-6.7 7.9H32V26Z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 26'%3E%3Cpath d='M0 26V15C0 6.5 4.6 1.2 12.5 0l1.2 3.7C9.4 5 7.2 7.6 7 11.6h6.2V26Zm18.8 0V15C18.8 6.5 23.4 1.2 31.3 0l1.2 3.7c-4.3 1.3-6.5 3.9-6.7 7.9H32V26Z'/%3E%3C/svg%3E") center/contain no-repeat}
[data-theme=leaf] .sec--dark .tcard blockquote{color:#fff}
[data-theme=leaf] .tcard figcaption img{width:4.4rem;height:4.4rem;border:3px solid #fff;box-shadow:0 8px 20px -8px rgba(0,0,0,.4)}
[data-theme=leaf] .tcard-who b{font:700 clamp(1.25rem,4vw,1.6rem)/1.2 var(--head-font)}
[data-theme=leaf] .tcard-who span{color:var(--gray);font-size:1rem}
[data-theme=leaf] .tsl-btn{border:0;background:#fff;color:var(--text);box-shadow:0 8px 20px -8px rgba(20,20,40,.4)}
[data-theme=leaf] .tsl-btn:hover{background:var(--fill);color:var(--fill-ink)}
[data-theme=leaf] .sec--quote{background:var(--soft)}
[data-theme=leaf] .quote blockquote{font:500 clamp(1.4rem,4.4vw,2.1rem)/1.5 var(--head-font);color:var(--text)}
[data-theme=leaf] .profile img{border-radius:0 var(--leaf-lg) 0 var(--leaf-lg)}
[data-theme=leaf] .sec--tint{background:var(--soft)}

/* ----- inner pages open with a cream area and a breadcrumb ----- */
[data-theme=leaf] .lf-title{position:relative;overflow:hidden;background:linear-gradient(180deg,var(--soft),var(--bg) 92%);padding-top:clamp(1.5rem,4vw,2.5rem)}
[data-theme=leaf] .lf-title::before{content:"";position:absolute;right:-34%;top:12%;width:min(48rem,120vw);aspect-ratio:1;border-radius:50%;border:1.5px solid color-mix(in srgb,var(--text) 10%,transparent);pointer-events:none}
[data-theme=leaf] .lf-title .wrap{position:relative}
[data-theme=leaf] .lf-crumbs{display:flex;align-items:center;gap:.5rem;margin:0 0 clamp(2.5rem,7vw,4rem);font:600 1.05rem/1.3 var(--head-font);letter-spacing:.02em}
[data-theme=leaf] .lf-crumbs a{color:var(--text);text-decoration:none}
[data-theme=leaf] .lf-crumbs a:hover{color:var(--brand-t)}
[data-theme=leaf] .lf-crumbs svg{width:1rem;height:1rem;color:var(--brand2-t)}
[data-theme=leaf] .lf-crumbs span{color:var(--gray)}
[data-theme=leaf] .lf-title-body{max-width:50rem}
[data-theme=leaf] .lf-title-body>div>p:first-of-type{font-size:clamp(1.12rem,3.4vw,1.3rem);color:var(--text)}

/* ----- call to action and footer ----- */
[data-theme=leaf] .cta{margin:clamp(2rem,6vw,4rem) clamp(.75rem,3vw,2rem);padding:clamp(3rem,8vw,5.5rem) 1.25rem;border-radius:0 var(--leaf-lg) 0 var(--leaf-lg);background:var(--deep);color:var(--deep-ink)}
[data-theme=leaf] .cta :is(h1,h2){color:#fff}
[data-theme=leaf] .cta :is(h1,h2)::after{margin-inline:auto;background:var(--brand2-d)}
[data-theme=leaf] .cta .btn{color:#fff;border-color:#fff}
[data-theme=leaf] .cta .btn--solid{background:var(--fill);color:var(--fill-ink);border-color:var(--fill)}
[data-theme=leaf] .ftr--leaf{margin-top:clamp(2rem,6vw,4rem);padding:clamp(3.5rem,8vw,5.5rem) 0 2rem;border-radius:var(--leaf-lg) 0 0 0;background:var(--ftr-bg);color:var(--ftr-ink)}
[data-theme=leaf] #main>.cta:last-child{margin-bottom:0}
[data-theme=leaf] .lf-f-grid{display:grid;gap:2.25rem;grid-template-columns:minmax(0,1fr)}
[data-theme=leaf] .lf-f-name{display:inline-flex;align-items:center;gap:.8rem;font:700 1.45rem/1.15 var(--head-font);color:#fff;text-decoration:none}
[data-theme=leaf] .lf-f-name img{height:3.4rem;width:auto;max-width:4.2rem;object-fit:contain}
[data-theme=leaf] .lf-f-line{margin:1rem 0 0;opacity:.85;max-width:24rem}
[data-theme=leaf] .ftr--leaf h3{margin:0 0 1rem;font:600 1.15rem/1.2 var(--head-font);letter-spacing:.04em;color:var(--brand2-d)}
[data-theme=leaf] .ftr--leaf address{font-style:normal;line-height:1.9}
[data-theme=leaf] .ftr--leaf a{text-decoration:none}
[data-theme=leaf] .ftr--leaf address a:hover,[data-theme=leaf] .lf-f-links a:hover{text-decoration:underline}
[data-theme=leaf] .lf-f-links li a{padding:.3rem 0;font-weight:500}
[data-theme=leaf] .ftr--leaf .social{display:flex;flex-wrap:wrap;gap:.6rem;margin:1.4rem 0 0}
[data-theme=leaf] .ftr--leaf .social li a{display:grid;place-items:center;width:2.8rem;height:2.8rem;padding:0;border-radius:50%;background:rgba(255,255,255,.14);transition:background .2s,transform .2s}
[data-theme=leaf] .ftr--leaf .social li a:hover{background:var(--fill);color:var(--fill-ink);transform:translateY(-3px);text-decoration:none}
[data-theme=leaf] .lf-f-apply{margin-top:1.6rem;background:var(--fill);color:var(--fill-ink);border-color:var(--fill)}
[data-theme=leaf] .ftr--leaf .legal{margin-top:3rem}
@media (min-width:52rem){[data-theme=leaf] .lf-f-grid{grid-template-columns:1.5fr 1fr 1fr 1fr;gap:3rem}}

/* the round back-to-top button, which fills as the page is scrolled */
.lf-top{position:fixed;right:1rem;bottom:1rem;z-index:65;display:grid;place-items:center;width:3.4rem;height:3.4rem;padding:0;border:0;border-radius:50%;background:conic-gradient(var(--brand) calc(var(--p,0)*1%),color-mix(in srgb,var(--brand) 18%,#fff) 0);color:var(--brand-t);cursor:pointer;box-shadow:0 12px 26px -10px rgba(0,0,0,.45);opacity:0;transform:translateY(1rem);pointer-events:none;transition:opacity .3s,transform .3s}
.lf-top::before{content:"";position:absolute;inset:3px;border-radius:50%;background:#fff}
.lf-top svg{position:relative;width:1.5rem;height:1.5rem}
.lf-top.on{opacity:1;transform:none;pointer-events:auto}
.lf-top:hover{transform:translateY(-3px)}
body:has(.chat-fab) .lf-top{bottom:1rem;right:5.4rem}
@media (min-width:40.01rem){body:has(.chat-fab) .lf-top{right:1rem;bottom:4.6rem}}
body.menu-open .lf-top{display:none}
@media print{.lf-top{display:none}}
@endverbatim
