@verbatim
/* ============ CREST: white bar with a round menu button, coral full-screen menu, crimson banner with a ring and an arrow, huge capital headlines, chevron-cornered buttons ============ */
[data-theme=crest]{--hdr-h:5.5rem;--cr-text2:color-mix(in srgb,var(--brand2-t) 76%,#000);--cr-art:color-mix(in srgb,var(--brand2) 90%,var(--band));--cr-v:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 40'%3E%3Cpath d='M2 6l6 6 6-6M2 18l6 6 6-6M2 30l6 6 6-6' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");--cr-v1:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 16'%3E%3Cpath d='M3 4l9 8 9-8' fill='none' stroke='%23000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E")}
[data-theme=crest],[data-theme=crest] body{line-height:1.75;font-size:1.125rem}
[data-theme=crest] h1,[data-theme=crest] h2,[data-theme=crest] h3{font-family:var(--head-font);font-weight:700;text-transform:uppercase;color:var(--brand-t);letter-spacing:.005em;line-height:1.12}
[data-theme=crest] h1{font-size:clamp(2.2rem,8vw,3.6rem)}
[data-theme=crest] h2{font-size:clamp(1.8rem,6.4vw,2.7rem)}
[data-theme=crest] h3{font-size:clamp(1.35rem,4.4vw,1.7rem)}
[data-theme=crest] .sec--dark h1,[data-theme=crest] .sec--dark h2,[data-theme=crest] .sec--dark h3,[data-theme=crest] .cta h1,[data-theme=crest] .cta h2{color:#fff}
[data-theme=crest] .sec{padding:clamp(3.5rem,9vw,6rem) 0}
[data-theme=crest] .sec--soft{background:var(--soft)}
[data-theme=crest] .sec--dark{background:var(--band);color:#fff}
[data-theme=crest] .prose p,[data-theme=crest] .sec-head p:not(.eyebrow){font-size:1.15rem;line-height:1.85;color:var(--text)}
[data-theme=crest] .sec--dark .prose p,[data-theme=crest] .sec--dark .sec-head p:not(.eyebrow){color:#fff}
[data-theme=crest] em,[data-theme=crest] .accent{font-style:normal;color:var(--cr-text2)}
[data-theme=crest] .sec--dark em,[data-theme=crest] .sec--dark .accent{color:var(--brand2-d)}
[data-theme=crest] .prose a{color:var(--cr-text2);font-weight:600}
[data-theme=crest] .prose ul li{padding-left:1.6rem}
[data-theme=crest] .prose ul li::before{left:0;top:.7em;width:.55rem;height:.55rem;background:var(--brand2)}
[data-theme=crest] .eyebrow{margin:0 0 .9rem;font:700 .85rem/1.3 var(--body-font);letter-spacing:.24em;text-transform:uppercase;color:var(--cr-text2)}
[data-theme=crest] .prose p.eyebrow{margin:0 0 .9rem;font-size:.85rem;line-height:1.3;color:var(--cr-text2)}
[data-theme=crest] .eyebrow::before{display:none}
[data-theme=crest] .sec--dark .eyebrow{color:#fff;text-align:center;font:600 clamp(1.05rem,3.6vw,1.4rem)/1.3 var(--head-font);letter-spacing:.02em;text-transform:uppercase}
[data-theme=crest] .sec--dark h1,[data-theme=crest] .sec--dark h2{text-align:center;color:var(--brand2-d);font-weight:800;font-size:clamp(2.8rem,14vw,6.2rem);line-height:.98}
[data-theme=crest] .sec--dark .sec-head{max-width:none;text-align:center}
[data-theme=crest] .split img{border-radius:0}
[data-theme=crest] .prose blockquote{margin-left:0;padding-left:1.25rem;border-left:.3rem solid var(--brand2);font:600 1.3rem/1.5 var(--head-font)}

/* buttons: wide flat capitals with a stack of three little chevrons in each corner */
[data-theme=crest] .btn,[data-theme=crest] .action,[data-theme=crest] .hdr .sbar button{position:relative;border:0;border-radius:0;box-shadow:none;transform:none;transition:background .18s,color .18s}
[data-theme=crest] .btn{min-height:3.1rem;padding:0 2.7rem;background:var(--fill);color:var(--fill-ink);font:700 .8rem/1.3 var(--body-font);letter-spacing:.22em;text-transform:uppercase;text-align:center;--cr-chev:var(--band)}
[data-theme=crest] .btn::before,[data-theme=crest] .btn::after,[data-theme=crest] .action::before,[data-theme=crest] .action::after{content:"";position:absolute;top:50%;width:.8rem;height:1.8rem;transform:translateY(-50%);background:var(--cr-chev,var(--band));-webkit-mask:var(--cr-v) center/contain no-repeat;mask:var(--cr-v) center/contain no-repeat;pointer-events:none}
[data-theme=crest] .btn::before,[data-theme=crest] .action::before{left:.75rem}
[data-theme=crest] .btn::after,[data-theme=crest] .action::after{right:.75rem}
[data-theme=crest] .btn:hover{background:var(--band);color:#fff;--cr-chev:var(--brand2-d);box-shadow:none}
[data-theme=crest] .btn--ghost{background:var(--band);color:#fff;--cr-chev:var(--brand2-d)}
[data-theme=crest] .btn--ghost:hover{background:var(--fill);color:var(--fill-ink);--cr-chev:var(--band)}
[data-theme=crest] .btns{flex-direction:column;align-items:stretch;gap:1rem}
[data-theme=crest] .btns .btn{width:100%}
@media (min-width:40rem){[data-theme=crest] .btns{flex-direction:row;flex-wrap:wrap;align-items:center}[data-theme=crest] .btns .btn{width:auto;min-width:16rem}}

/* header: white bar, the crest and a spaced capital name on the left, search and a round coral menu button on the right */
[data-theme=crest] .hdr{background:#fff;color:var(--text);box-shadow:none;border-bottom:0}
[data-theme=crest] .hdr-in{min-height:var(--hdr-h);gap:.75rem}
[data-theme=crest] .brand{gap:1rem;color:var(--hdr-name)}
[data-theme=crest] .brand img{height:4rem;width:auto;max-width:5.2rem}
[data-theme=crest] .brand span{overflow:visible;text-overflow:clip;white-space:normal;font:800 clamp(1.15rem,5vw,1.55rem)/1.15 var(--head-font);letter-spacing:.1em;text-transform:uppercase;color:var(--brand-t)}
[data-theme=crest] .hdr-tools{gap:.5rem}
[data-theme=crest] .sbtn{color:var(--brand-t);width:3rem;height:3rem}
[data-theme=crest] .sbtn svg{width:1.6rem;height:1.6rem}
[data-theme=crest] .burger{width:3.6rem;height:3.6rem;padding:0 1.05rem;border-radius:50%;background:var(--brand2);color:var(--brand2-ink);transition:background .25s,color .25s}
[data-theme=crest] body.scrolled .burger{background:var(--band);color:#fff}
[data-theme=crest] .burger i{width:100%;height:2px}
[data-theme=crest] .hdr .sbar{background:#fff;color:var(--text);border-top:1px solid var(--line);box-shadow:0 22px 34px -18px rgba(0,0,0,.4)}
[data-theme=crest] .sbar input{border:0;border-bottom:2px solid var(--band);border-radius:0;background:transparent;font-size:1.2rem}
[data-theme=crest] .sbar input:focus{box-shadow:none;border-color:var(--brand2)}
[data-theme=crest] .hdr .sbar button{min-height:3.2rem;padding:0 1.6rem;background:var(--band);color:#fff;font:700 .82rem/1 var(--body-font);letter-spacing:.24em;text-transform:uppercase}
[data-theme=crest] .hdr .sbar button:hover{background:var(--fill);color:var(--fill-ink)}

/* the menu: a coral sheet under the bar, plain white words, hairlines, crimson chevrons */
[data-theme=crest] .menu{inset:var(--hdr-h) 0 0 0;padding:0;background:var(--fill-lg);color:var(--fill-lg-ink);z-index:60}
body[data-theme=crest] .menu .menu-sub a{font:700 1.2rem/1.3 var(--body-font);opacity:1}
.cr-menu-in{max-width:46rem;margin:0 auto;padding:.5rem 2.25rem 4rem}
@media (min-width:40rem){.cr-menu-in{padding-left:3rem;padding-right:3rem}}
@media (min-width:60rem){
  [data-theme=crest] .burger{display:inline-flex}
  [data-theme=crest] .menu{display:none;position:fixed;inset:var(--hdr-h) 0 0 0;align-items:normal;gap:0;background:var(--fill-lg);color:var(--fill-lg-ink);padding:0;overflow:auto}
  body.menu-open[data-theme=crest] .menu{display:block}
}
[data-theme=crest] .menu-list{list-style:none;margin:0;padding:0}
[data-theme=crest] .menu .menu-list>li>a{border-radius:0;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.6rem 0;border:0;border-bottom:1px solid color-mix(in srgb,currentColor 75%,transparent);background:none;font:400 clamp(1.5rem,6vw,1.75rem)/1.25 var(--body-font);letter-spacing:0;text-transform:none}
[data-theme=crest] .menu .menu-list>li>a svg{width:1.2rem;height:1.2rem;flex:none;color:var(--band)}
[data-theme=crest] .menu .menu-list a[aria-current]{text-decoration:none;font-weight:700}
[data-theme=crest] .menu .menu-list>li>a:hover{opacity:.85}
.cr-menu-small{list-style:none;margin:2rem 0 0;padding:0;display:grid;gap:.9rem}
[data-theme=crest] .menu .cr-menu-small a{display:inline;padding:0;border:0;font:700 1.2rem/1.3 var(--body-font);letter-spacing:.02em;text-decoration:underline;text-underline-offset:.25em}

/* the banner: a crimson field, a big coral ring, a coral arrow pointing into a coral block */
[data-theme=crest] .hero--crest{display:block;min-height:0;padding:0;background:var(--band);color:#fff;overflow:hidden}
[data-theme=crest] .hero--crest::before,[data-theme=crest] .hero--crest::after{display:none}
.cr-chev{position:absolute;left:0;right:0;top:0;height:5.5rem;background:var(--cr-art);opacity:.85;-webkit-mask:var(--cr-v1) 0 .6rem/3.1rem 2rem repeat-x,var(--cr-v1) 1.55rem 3.2rem/3.1rem 2rem repeat-x;mask:var(--cr-v1) 0 .6rem/3.1rem 2rem repeat-x,var(--cr-v1) 1.55rem 3.2rem/3.1rem 2rem repeat-x;pointer-events:none}
[data-theme=crest] .hero--crest .cr-in{position:relative;z-index:2;padding-top:clamp(7rem,22vw,9rem);padding-bottom:0;text-align:center}
.cr-kick{margin:0 0 .5rem;font:400 clamp(1.4rem,5.4vw,2.1rem)/1.2 var(--body-font);text-shadow:0 1px 14px rgba(0,0,0,.25)}
[data-theme=crest] .hero--crest h1,[data-theme=crest] .hero--crest h2{max-width:14ch;margin:0 auto;color:#fff;font-weight:800;font-size:clamp(2.6rem,11.5vw,6rem);line-height:1.02;letter-spacing:0;text-shadow:0 2px 22px rgba(0,0,0,.28)}
[data-theme=crest] .hero--crest em{color:inherit}
[data-theme=crest] .hero--crest .hero-sub{margin:1.25rem auto 0;max-width:34rem;font-size:1.15rem;opacity:.96}
[data-theme=crest] .hero--crest .cr-in .btns{margin-top:1.75rem;justify-content:center}
.cr-arrow{position:relative;z-index:1;display:block;width:94%;max-width:72rem;height:clamp(8rem,30vw,15rem);margin:calc(-1 * clamp(4rem,14vw,7rem)) auto 0;background:var(--cr-art);clip-path:polygon(0 0,100% 0,100% 38%,50% 100%,0 38%)}
.cr-in:has(.hero-sub)+.cr-arrow{margin-top:clamp(1.25rem,4vw,2.5rem)}
.cr-base{position:relative;z-index:1;width:94%;max-width:72rem;margin:0 auto;min-height:clamp(7rem,24vw,12rem);padding:2rem 1.25rem;display:flex;align-items:center;justify-content:center;background:var(--cr-art);overflow:hidden}
.cr-base .btns{width:100%;max-width:22rem;justify-content:center}
.cr-base .btn{background:var(--band);color:#fff;--cr-chev:var(--brand2-d)}
.cr-base .btn:hover{background:#fff;color:var(--band);--cr-chev:var(--brand2)}
.cr-base .btn--ghost{background:transparent;box-shadow:inset 0 0 0 2px #fff}
.cr-base .btn--ghost:hover{background:#fff;color:var(--band)}
.cr-base:has(img){min-height:0;padding:0;aspect-ratio:16/10;max-height:34rem}
.cr-base img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
@media (min-width:60rem){
  [data-theme=crest] .hero--crest .cr-in{padding-top:9.5rem}
  .cr-arrow{height:14rem;margin-top:-6rem}
  .cr-base{min-height:11rem}
}

/* quick actions: stacked flat buttons */
[data-theme=crest] .actions{margin-top:0;padding:clamp(2.5rem,6vw,3.5rem) 0 0}
[data-theme=crest] .actions-grid{gap:1rem}
[data-theme=crest] .action{justify-content:center;min-height:3.2rem;padding:.75rem 2.7rem;background:var(--band);color:#fff;text-align:center;outline:0;--cr-chev:var(--brand2-d)}
[data-theme=crest] .action:nth-child(even){background:var(--fill);color:var(--fill-ink);--cr-chev:var(--band)}
[data-theme=crest] .action:hover{transform:none;filter:brightness(1.06)}
[data-theme=crest] .action-ico,[data-theme=crest] .action-go{display:none}
[data-theme=crest] .action-text{flex:0 1 auto}
[data-theme=crest] .action-text b{font:700 .92rem/1.3 var(--body-font);letter-spacing:.28em;text-transform:uppercase}
[data-theme=crest] .action-text span{color:inherit;opacity:.9;font-size:.9rem}
@media (min-width:60rem){[data-theme=crest] .actions-grid{grid-template-columns:repeat(3,1fr)}}

/* cards: photos with the title laid over their lower edge in coral capitals */
[data-theme=crest] .cards{gap:2.5rem 1.75rem}
[data-theme=crest] .card{border:0;border-radius:0;background:transparent;box-shadow:none;overflow:visible}
[data-theme=crest] .card:hover{transform:none;box-shadow:none}
[data-theme=crest] .card-body{padding:1.25rem 0 0}
[data-theme=crest] .card p{color:var(--text)}
[data-theme=crest] .card--plain{border-top:.45rem solid var(--brand2)}
[data-theme=crest] .card--plain .card-body{padding:1.5rem 0 0}
[data-theme=crest] .card--plain::before{display:none}
[data-theme=crest] .card-ico{border-radius:50%;background:var(--brand2);color:var(--brand2-ink)}
[data-theme=crest] .card:has(> img){display:grid}
[data-theme=crest] .card:has(> img)>img{grid-area:1/1;aspect-ratio:3/2;width:100%}
[data-theme=crest] .card:has(> img)>.card-body{display:contents}
[data-theme=crest] .card:has(> img) h3{grid-area:1/1;align-self:end;z-index:1;margin:0;padding:1.25rem 1rem 1.1rem;max-width:100%;font-weight:800;font-size:clamp(1.55rem,6vw,2.3rem);line-height:1.05;color:var(--brand2-d);text-shadow:0 2px 14px rgba(0,0,0,.6),0 0 2px rgba(0,0,0,.4)}
[data-theme=crest] .card:has(> img) p{grid-area:2/1;padding-top:1.1rem}
[data-theme=crest] .panel-card{background:var(--band);color:#fff;padding:.5rem}
[data-theme=crest] .panel-card h3,[data-theme=crest] .panel-card p{color:#fff}
[data-theme=crest] .pillar-n{margin:0 0 .5rem;font:700 .8rem/1 var(--body-font);letter-spacing:.24em;text-transform:uppercase;color:var(--cr-text2)}

/* steps: a plain ruled list of capital titles with a big coral number */
[data-theme=crest] .steps{gap:0}
[data-theme=crest] .steps li{display:grid;grid-template-columns:3.4rem 1fr;gap:0 1rem;padding:1.5rem 0;border:0;border-top:1px solid var(--line);border-radius:0;background:transparent}
[data-theme=crest] .steps li:last-child{border-bottom:1px solid var(--line)}
[data-theme=crest] .sec--soft .steps li{background:transparent}
[data-theme=crest] .step-n{width:auto;height:auto;margin:0;border-radius:0;background:none;box-shadow:none;place-items:start;font:800 2.6rem/1 var(--head-font);color:var(--brand2-t)}
[data-theme=crest] .steps li::after{display:none!important}
[data-theme=crest] .steps h3{margin:.15rem 0 .35rem}
[data-theme=crest] .steps p{color:var(--text)}
@media (min-width:44rem){[data-theme=crest] .steps{grid-template-columns:1fr 1fr;column-gap:3rem}[data-theme=crest] .steps li:nth-last-child(2):nth-child(odd){border-bottom:1px solid var(--line)}}

/* numbers: a crimson band with huge coral figures */
[data-theme=crest] .sec--stats{background:var(--band);color:#fff}
[data-theme=crest] .sec--stats h2{color:#fff;margin-bottom:1.5rem}
[data-theme=crest] .stat{border-left-color:rgba(255,255,255,.28)}
[data-theme=crest] .stat b,[data-theme=crest] .stats strong{font:800 clamp(2.8rem,10vw,5rem)/1 var(--head-font);color:var(--brand2-d)}
[data-theme=crest] .stat span{color:#fff;font:700 .8rem/1.4 var(--body-font);letter-spacing:.2em;text-transform:uppercase;opacity:.92}

/* news: unframed pictures, coral label, crimson capital headline */
[data-theme=crest] .news-item{border:0;border-radius:0;background:transparent}
[data-theme=crest] .news-body{padding:1.25rem 0 0}
[data-theme=crest] .news-item h3{font-size:clamp(1.25rem,4vw,1.5rem)}
[data-theme=crest] .news-item p{color:var(--text)}
[data-theme=crest] .tag{color:var(--cr-text2);font-weight:700;letter-spacing:.2em;text-transform:uppercase;font-size:.78rem}
[data-theme=crest] .more{color:var(--cr-text2);font-weight:700;letter-spacing:.18em;text-transform:uppercase;font-size:.82rem}

/* call to action */
[data-theme=crest] .cta{background:var(--band);color:#fff}
[data-theme=crest] .cta .btn--solid{background:var(--fill);color:var(--fill-ink);--cr-chev:var(--band)}
[data-theme=crest] .cta .btn--ghost{background:transparent;box-shadow:inset 0 0 0 2px #fff;color:#fff;--cr-chev:#fff}
[data-theme=crest] .cta .btn--ghost:hover{background:#fff;color:var(--band);--cr-chev:var(--band)}

/* testimonials: a crimson panel, centred words, coral capital name, round coral arrows */
[data-theme=crest] .tcard{align-items:center;border:0;border-radius:0;background:var(--band);color:#fff;text-align:center;padding:clamp(2.5rem,7vw,4rem) clamp(1.5rem,5vw,3rem)}
[data-theme=crest] .tcard blockquote{font:400 clamp(1.2rem,3.6vw,1.45rem)/1.65 var(--body-font);font-style:normal}
[data-theme=crest] .tcard figcaption{flex-direction:column;text-align:center}
[data-theme=crest] .tcard-who{align-items:center}
[data-theme=crest] .tcard-who b{font:800 1.2rem/1.3 var(--head-font);letter-spacing:.14em;text-transform:uppercase;color:var(--brand2-d)}
[data-theme=crest] .tcard-who span,[data-theme=crest] .sec--dark .tcard-who span{color:#fff;opacity:.9}
[data-theme=crest] .sec--dark .tcard{background:var(--deep);border:0}
[data-theme=crest] .tsl-btn{width:3.6rem;height:3.6rem;border:0;background:var(--fill);color:var(--fill-ink)}
[data-theme=crest] .sec--dark .tsl-btn{color:var(--fill-ink)}
[data-theme=crest] .tsl-dots i{background:var(--band)}
[data-theme=crest] .sec--dark .tsl-dots i{background:#fff}

/* footer: crimson, centred, coral capital headings, white underlined links, a social row and the small print */
.ftr--crest{background:color-mix(in srgb,var(--band) 90%,#000);color:#fff;padding:0;text-align:center}
.cr-f{padding-top:clamp(3.5rem,9vw,5.5rem);padding-bottom:3rem}
.cr-f-name{display:block;font:800 clamp(1.7rem,6.5vw,2.5rem)/1.1 var(--head-font);letter-spacing:.1em;text-transform:uppercase;color:#fff;text-decoration:none}
.cr-f-tag{margin:.8rem 0 0;font-size:1.1rem;opacity:.92}
.cr-f-cols{display:grid;gap:2.75rem;margin-top:2.75rem}
.cr-f-col h3{margin:0 0 1rem;font:800 clamp(1.4rem,5vw,1.9rem)/1.15 var(--head-font);letter-spacing:.02em;text-transform:uppercase;color:var(--brand2-d)}
.cr-f-col p{margin:0 0 1rem;line-height:1.8}
.ftr--crest a{color:#fff;text-underline-offset:.2em}
.ftr--crest .cr-f-col a,.ftr--crest .cr-f-links a{text-decoration:underline}
.ftr--crest .cr-f-links li{margin:0}
.ftr--crest .cr-f-links li a{padding:.3rem 0;font-weight:400;text-decoration:underline;font-size:1.1rem}
.cr-f-links{list-style:none;margin:0;padding:0}
.cr-f-base{background:var(--band);padding:2.25rem 0 2.75rem}
.cr-f-base .social{justify-content:center;gap:1.1rem;margin:0 0 1.5rem}
.ftr--crest .social li{margin:0}
.ftr--crest .social li a{width:3rem;height:3rem;padding:0;background:none;color:#fff}
.ftr--crest .social li a svg{width:1.7rem;height:1.7rem}
.ftr--crest .social li a:hover{background:rgba(255,255,255,.16)}
.cr-f-base p{margin:.3rem 0;font-size:.95rem;opacity:.92}
@media (min-width:48rem){.cr-f-cols{grid-template-columns:1fr 1fr;max-width:54rem;margin-left:auto;margin-right:auto}}

/* inner pages and forms */
[data-theme=crest] input,[data-theme=crest] select,[data-theme=crest] textarea{border-radius:0}
[data-theme=crest] .pagehero{background:var(--band);color:#fff}
[data-theme=crest] .pagehero::before{display:none}
[data-theme=crest] .pagehero::after{content:"";position:absolute;right:-7rem;top:-6rem;width:22rem;aspect-ratio:1;border-radius:50%;border:4.5rem solid var(--cr-art);z-index:-1;box-sizing:border-box}
[data-theme=crest] .pagehero h1{color:#fff;font-weight:800;font-size:clamp(2.2rem,8vw,3.8rem);max-width:18ch}
[data-theme=crest] .pagehero .hero-tag{background:none;border:0;padding:0;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:#fff}
[data-theme=crest] .page-head h1,[data-theme=crest] .page-head h2{color:var(--brand-t)}
@endverbatim
