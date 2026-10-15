@verbatim
/* ============ HERITAGE: gold header, red full-screen menu, big serif headlines, photo banners, gold footer ============ */
[data-theme=heritage]{--hdr-h:3.7rem;--maroon:color-mix(in srgb,var(--brand) 68%,#000);--ink:#141210}
[data-theme=heritage] body,body[data-theme=heritage]{color:var(--ink)}
[data-theme=heritage] h1,[data-theme=heritage] h2,[data-theme=heritage] h3{font-weight:400;letter-spacing:-.012em;line-height:1.1;text-wrap:wrap}
[data-theme=heritage] p{text-wrap:wrap}
[data-theme=heritage] a{text-underline-offset:.18em}

/* ---- buttons: outlined, spaced capitals, softly rounded ---- */
[data-theme=heritage] .btn{min-height:3.15rem;padding:.8rem 2.2rem;border-radius:.75rem;border-width:2px;font:500 1rem/1.2 var(--body-font);letter-spacing:.17em;text-transform:uppercase}
[data-theme=heritage] .btn:hover{transform:none}
[data-theme=heritage] .btn--ghost{color:var(--ink);border-color:var(--ink);background:transparent}
[data-theme=heritage] .btn--ghost:hover{background:var(--ink);color:#fff}
[data-theme=heritage] .btn--solid{background:var(--brand);border-color:var(--brand);color:var(--brand-ink);border-radius:.4rem;letter-spacing:.14em}
[data-theme=heritage] .btn--solid:hover{box-shadow:none;background:var(--maroon);border-color:var(--maroon)}
[data-theme=heritage] .btns{gap:1rem}

/* ---- header: gold bar, school name in red serif, thick burger ---- */
[data-theme=heritage] .hdr{box-shadow:none;border:0}
[data-theme=heritage] .hdr-in{min-height:var(--hdr-h)}
[data-theme=heritage] .brand img{height:2.6rem;max-width:3.2rem}
[data-theme=heritage] .brand span{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;font:600 1.55rem/1 var(--head-font);letter-spacing:-.02em;color:var(--hdr-name);white-space:normal}
[data-theme=heritage] .burger{width:2.6rem;height:2.6rem;padding:.55rem .35rem;gap:.42rem;color:var(--hdr-ink)}
[data-theme=heritage] .burger i{height:3px;border-radius:0}
[data-theme=heritage].menu-open .burger i:nth-child(1){transform:translateY(.45rem) rotate(45deg)}
[data-theme=heritage].menu-open .burger i:nth-child(3){transform:translateY(-.45rem) rotate(-45deg)}
/* the red menu */
[data-theme=heritage] .menu{display:none;flex-direction:column;padding:0;background:var(--band);color:#fff}
[data-theme=heritage].menu-open .menu{display:flex}
[data-theme=heritage] .menu-main{padding:1.1rem 1.9rem 1rem}
[data-theme=heritage] .menu a{border:0;opacity:1;font-family:var(--head-font)}
[data-theme=heritage] .menu-main a{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.7rem 0;font-size:1.75rem;line-height:1.15;letter-spacing:-.01em}
[data-theme=heritage] .menu-main a svg{flex:none;width:1.5rem;height:1.5rem;opacity:.9}
[data-theme=heritage] .menu-main a[aria-current]{text-decoration:underline;text-decoration-thickness:2px;text-underline-offset:.28em}
[data-theme=heritage] .menu-alt{margin:0 1.9rem;padding:1.1rem 0 1.4rem;border-top:1px solid color-mix(in srgb,#000 35%,var(--brand))}
[data-theme=heritage] .menu-alt a{display:block;padding:.65rem 0;font-size:1.75rem;font-style:italic;line-height:1.15}
[data-theme=heritage] .menu-util{margin-top:auto;padding:1.6rem 1.9rem 3rem;background:var(--maroon)}
[data-theme=heritage] .menu-util a{display:block;padding:.45rem 0;font:400 1.15rem/1.3 var(--body-font)}
@media (min-width:60rem){
  [data-theme=heritage]{--hdr-h:5rem}
  [data-theme=heritage] .burger{display:none}
  [data-theme=heritage] .menu,[data-theme=heritage].menu-open .menu{display:flex;flex-direction:row;align-items:center;gap:0;background:none;color:var(--ink)}
  [data-theme=heritage] .menu-main{display:flex;align-items:center;gap:.25rem;padding:0}
  [data-theme=heritage] .menu-main a{padding:.5rem .8rem;font-size:1.3rem;border-radius:0;border-bottom:3px solid transparent}
  [data-theme=heritage] .menu-main a svg{display:none}
  [data-theme=heritage] .menu-main a:hover{background:none;border-bottom-color:var(--brand)}
  [data-theme=heritage] .menu-main a[aria-current]{text-decoration:none;border-bottom-color:var(--brand);background:none}
  [data-theme=heritage] .menu-alt,[data-theme=heritage] .menu-util{display:none}
  [data-theme=heritage] .brand span{font-size:1.9rem}
  [data-theme=heritage] .brand img{height:3.2rem;max-width:3.8rem}
}
/* the thin strip above the header (large screens) */
[data-theme=heritage] .top{background:var(--maroon);color:#fff;font-size:.9rem}
[data-theme=heritage] .top-links a{font-weight:600;letter-spacing:.02em}

/* ---- section rhythm and type ---- */
[data-theme=heritage] .sec{padding:clamp(3.5rem,8vw,5.5rem) 0}
[data-theme=heritage] .sec--soft{background:#f6f2ea}
[data-theme=heritage] .sec h2,[data-theme=heritage] .sec h1{font-size:clamp(2.25rem,8.5vw,3.9rem)}
[data-theme=heritage] .sec-head{max-width:none;margin-bottom:2.25rem}
[data-theme=heritage] .sec-head p{color:inherit;opacity:.85;font-size:1.15rem}
[data-theme=heritage] .eyebrow{font:700 .85rem/1 var(--body-font);letter-spacing:.18em;color:var(--brand-t)}
[data-theme=heritage] .eyebrow::before{display:none}
[data-theme=heritage] .sec--dark .eyebrow,[data-theme=heritage] .hh-tag{color:var(--brand2-d)}
[data-theme=heritage] .sec--dark{background:var(--deep);color:#fff}
[data-theme=heritage] .prose p,[data-theme=heritage] .prose li{font-size:1.1rem;line-height:1.65}
[data-theme=heritage] .prose a{color:var(--ink)}
[data-theme=heritage] .prose ul li::before{content:"";background:var(--brand-t);border-radius:0;width:.55rem;height:.55rem;top:.7em}
/* centred statement text: big serif, like a banner announcement */
[data-theme=heritage] .center.prose{max-width:50rem}
[data-theme=heritage] .center.prose p{font:400 clamp(1.4rem,5vw,1.9rem)/1.55 var(--head-font);max-width:none}
[data-theme=heritage] .center.prose a{text-decoration-thickness:2px}
[data-theme=heritage] .sec--dark .prose p{color:#fff}
[data-theme=heritage] .split img{aspect-ratio:3/2;border-radius:0}

/* ---- the banner: a photo with a dark veil, or the school's red ---- */
.hh{position:relative;isolation:isolate;display:flex;align-items:center;justify-content:center;text-align:center;color:#fff;min-height:min(32rem,calc(100svh - var(--hdr-h,3.7rem)));overflow:hidden;background:var(--band)}
.hh--photo::before{content:"";position:absolute;inset:0;z-index:-1;background:rgba(10,6,6,.46)}
.hh-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
.hh-in{padding-top:clamp(4rem,12vw,7rem);padding-bottom:clamp(4rem,12vw,7rem)}
.hh-tag{margin:0 0 1.25rem;font:700 .85rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase}
.hh h1,.hh h2{color:#fff;font-size:clamp(2.4rem,10.5vw,4.9rem);line-height:1.04;letter-spacing:-.02em;max-width:16ch;margin:0 auto .5em}
.hh em{font-style:italic}
.hh-sub{max-width:44rem;margin:0 auto 2rem;font-size:clamp(1.1rem,3.4vw,1.3rem);line-height:1.6}
.hh-btns{display:flex;flex-direction:column;align-items:center;gap:1rem;margin-top:1.75rem}
.hh .btn--ghost{color:#fff;border-color:#fff;min-width:15rem}
.hh .btn--ghost:hover{background:#fff;color:var(--brand-t)}
@media (min-width:44rem){.hh-btns{flex-direction:row;justify-content:center}.hh{min-height:min(40rem,calc(100svh - var(--hdr-h,5rem)))}}

/* ---- numbers: four columns divided by thin black rules, like a countdown ---- */
[data-theme=heritage] .sec--stats{text-align:center}
[data-theme=heritage] .sec--stats h2{margin-bottom:1.75rem}
[data-theme=heritage] .stats{grid-template-columns:repeat(auto-fit,minmax(4.6rem,1fr));max-width:56rem;margin:0 auto}
[data-theme=heritage] .stat{padding:.75rem .35rem 1rem;border-left:2px solid var(--ink)}
[data-theme=heritage] .stat:first-child{border-left:0}
[data-theme=heritage] .stat b{color:var(--brand-t);font-size:clamp(1.6rem,7.4vw,3.4rem);font-weight:400}
[data-theme=heritage] .stat b small{font-size:.6em}
[data-theme=heritage] .stat span{margin-top:.5rem;font:400 clamp(1rem,3.6vw,1.3rem)/1.25 var(--head-font);color:var(--ink)}

/* ---- cards: rounded white cards in a row you swipe through ---- */
[data-theme=heritage] .cards{display:flex;gap:1rem;overflow-x:auto;scroll-snap-type:x mandatory;scroll-padding:0 1.25rem;margin:0 -1.25rem;padding:.25rem 1.25rem 1rem;scrollbar-width:thin;-webkit-overflow-scrolling:touch}
[data-theme=heritage] .card{flex:0 0 min(80%,19rem);scroll-snap-align:start;background:#fff;color:var(--ink);border:0;border-radius:1.75rem;box-shadow:none}
[data-theme=heritage] .card:hover{transform:none;box-shadow:none}
[data-theme=heritage] .card img{aspect-ratio:16/10}
[data-theme=heritage] .card-body{padding:1.5rem 1.6rem 2rem}
[data-theme=heritage] .card h3{font-size:1.65rem;line-height:1.12;margin-bottom:.5rem}
[data-theme=heritage] .card p{color:#3d3a36;font-size:1.05rem;line-height:1.55}
[data-theme=heritage] .card-ico{border-radius:0;background:none;color:var(--brand-t);width:auto;height:auto;margin-bottom:.9rem}
[data-theme=heritage] .card-ico .ico{width:2.4rem;height:2.4rem}
[data-theme=heritage] .card--plain::before{height:.4rem;background:var(--brand)}
[data-theme=heritage] .sec--soft .card{border:1px solid #e4ddcf}
[data-theme=heritage] .cards--pillars .card,[data-theme=heritage] .cards--panels .card{border-radius:0}
[data-theme=heritage] .pillar-n{font:700 .85rem/1 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--brand-t);margin-bottom:.6rem}
@media (min-width:52rem){
  [data-theme=heritage] .cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(15.5rem,1fr));overflow:visible;margin:0;padding:0}
  [data-theme=heritage] .card{flex:none}
}

/* ---- steps: big red serif numbers in ruled rows ---- */
[data-theme=heritage] .steps{gap:0;counter-reset:none}
[data-theme=heritage] .steps li{display:grid;grid-template-columns:3.4rem 1fr;gap:0 1rem;align-items:start;padding:1.6rem 0;background:none;border:0;border-top:1px solid color-mix(in srgb,currentColor 25%,transparent);border-radius:0}
[data-theme=heritage] .steps li:last-child{border-bottom:1px solid color-mix(in srgb,currentColor 25%,transparent)}
[data-theme=heritage] .steps li::after{display:none!important}
[data-theme=heritage] .step-n{all:unset;font:400 2.8rem/1 var(--head-font);color:var(--brand-t)}
[data-theme=heritage] .sec--dark .step-n{color:var(--brand2-d)}
[data-theme=heritage] .steps h3{font-size:1.6rem}
[data-theme=heritage] .steps p{color:inherit;opacity:.85;font-size:1.08rem}
@media (min-width:44rem){[data-theme=heritage] .steps{grid-template-columns:1fr}}
@media (min-width:64rem){[data-theme=heritage] .steps{grid-template-columns:repeat(2,1fr);column-gap:3rem}[data-theme=heritage] .steps li:nth-last-child(2):nth-child(odd){border-bottom:1px solid color-mix(in srgb,currentColor 25%,transparent)}}

/* ---- headlines: pictures with no frame, red label, serif title, red date ---- */
[data-theme=heritage] .sec--headlines h2,[data-theme=heritage] .hp-head h1{margin-bottom:1.1em}
[data-theme=heritage] .news{gap:2.4rem 2rem}
[data-theme=heritage] .news-item{background:none;border:0;border-radius:0;overflow:visible}
[data-theme=heritage] .news-item img{aspect-ratio:16/9;border-radius:0}
[data-theme=heritage] .news-body{padding:1.4rem 0 0;gap:.55rem}
[data-theme=heritage] .tag{font:700 1.02rem/1 var(--body-font);letter-spacing:.02em;color:var(--brand-t);text-transform:uppercase}
[data-theme=heritage] .news-item h3{font-size:clamp(1.5rem,5.4vw,1.85rem);line-height:1.13;margin:.15rem 0}
[data-theme=heritage] .news-item p{color:var(--ink);font-size:1.1rem;line-height:1.6}
[data-theme=heritage] .date{font:400 1rem/1 var(--body-font);letter-spacing:.02em;text-transform:uppercase;color:var(--brand-t);margin-top:.6rem}
[data-theme=heritage] .news-item h3 a:hover{color:var(--brand-t);text-decoration:none}
@media (min-width:44rem){[data-theme=heritage] .news--stack{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (min-width:64rem){
  [data-theme=heritage] .news--stack{grid-template-columns:repeat(3,minmax(0,1fr))}
  [data-theme=heritage] .news--lead .news-item:first-child{grid-column:1/-1;display:grid;grid-template-columns:1.5fr 1fr;gap:2.5rem;align-items:center}
  [data-theme=heritage] .news--lead .news-item:first-child .news-body{padding:0}
  [data-theme=heritage] .news--lead .news-item:first-child h3{font-size:2.6rem}
}
[data-theme=heritage] .more-row{display:flex;justify-content:center;margin:2.75rem 0 0}
[data-theme=heritage] .hp-filter{display:flex;flex-wrap:wrap;gap:.4rem 1.6rem;margin:-.4rem 0 0}
[data-theme=heritage] .hp-filter a{padding:.4rem 0;border-bottom:3px solid transparent;color:var(--ink);text-decoration:none;font:700 .95rem/1.2 var(--body-font);letter-spacing:.12em;text-transform:uppercase}
[data-theme=heritage] .hp-filter a:hover,[data-theme=heritage] .hp-filter a[aria-current]{border-bottom-color:var(--brand);color:var(--brand-t)}
[data-theme=heritage] .pager{gap:1.5rem}

/* ---- call to action: the school's red, big serif line, stacked outlined buttons ---- */
[data-theme=heritage] .cta{padding:clamp(3.5rem,9vw,6rem) 0}
[data-theme=heritage] .cta h1,[data-theme=heritage] .cta h2{font-size:clamp(2.2rem,8.5vw,3.6rem);color:#fff;margin-bottom:.55em}
[data-theme=heritage] .cta p{color:#fff;font-size:1.2rem}
[data-theme=heritage] .cta .btns{flex-direction:column;max-width:40rem;margin:0 auto}
[data-theme=heritage] .cta .btn{width:100%;flex:none;color:#fff;border-color:#fff}
[data-theme=heritage] .cta .btn:hover{background:#fff;color:var(--brand-t)}
@media (min-width:44rem){[data-theme=heritage] .cta .btns{flex-direction:row}}

/* ---- quick links, contact, faq, quote, person ---- */
[data-theme=heritage] .actions{margin:0;padding:clamp(2.5rem,6vw,4rem) 0;background:#f6f2ea}
[data-theme=heritage] .actions-grid{gap:0}
[data-theme=heritage] .action{padding:1.3rem 0;background:none;border-radius:0;box-shadow:none;border-top:1px solid #cfc6b4;color:var(--ink)}
[data-theme=heritage] .action:last-child{border-bottom:1px solid #cfc6b4}
[data-theme=heritage] a.action:hover{transform:none;box-shadow:none;border-color:var(--ink)}
[data-theme=heritage] .action-ico{background:none;width:auto;height:auto;border-radius:0;color:var(--brand-t)}
[data-theme=heritage] .action-text b{font:400 1.6rem/1.15 var(--head-font)}
[data-theme=heritage] .action-text span{color:#3d3a36}
@media (min-width:56rem){[data-theme=heritage] .actions-grid{gap:0 2.5rem}[data-theme=heritage] .action:last-child{border-bottom:0}[data-theme=heritage] .action:nth-last-child(-n+3){border-bottom:1px solid #cfc6b4}}
[data-theme=heritage] .contact{gap:2rem}
[data-theme=heritage] .contact .card{background:none;border:0;border-radius:0;flex:none}
[data-theme=heritage] .contact .card--plain::before{height:3px;width:3rem;background:var(--brand)}
[data-theme=heritage] .contact .card-body{padding:1rem 0 0;font-size:1.25rem}
[data-theme=heritage] .contact .card-body b{color:var(--brand-t);letter-spacing:.18em}
[data-theme=heritage] .faq summary{font:400 1.5rem/1.25 var(--head-font)}
[data-theme=heritage] .faq details p{color:var(--ink);font-size:1.1rem}
[data-theme=heritage] .quote{margin:0 auto;padding:0;border:0;text-align:center;max-width:52rem}
[data-theme=heritage] .quote blockquote{margin:0;padding:0;border:0}
[data-theme=heritage] .quote p{font:400 clamp(1.7rem,6vw,2.6rem)/1.3 var(--head-font);font-style:italic}
[data-theme=heritage] .quote figcaption{margin-top:1rem;font:700 .9rem/1 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--brand-t)}
[data-theme=heritage] .sec--tint{background:#f6f2ea}
[data-theme=heritage] .profile img{border-radius:0}

/* ---- gold footer ---- */
[data-theme=heritage] .ftr--heritage{padding:clamp(3rem,8vw,5rem) 0 2.5rem;text-align:left;color:var(--ink)}
.ftr--heritage a{color:var(--ink)}
.fh-brand{display:flex;align-items:center;justify-content:center;gap:.9rem;margin:0 auto;text-decoration:none;text-align:center}
.fh-brand img{height:5rem;width:auto;max-width:5rem;object-fit:contain}
.fh-brand span{font:500 clamp(1.9rem,8vw,2.7rem)/1 var(--head-font);letter-spacing:-.02em;color:var(--ink)}
.fh-visit{margin:2.4rem 0 0;text-align:center;font-size:1.15rem}
.fh-visit p{margin:0 0 .8rem}
.fh-visit a{text-decoration:underline}
.fh-strong{font-weight:700;text-decoration:none!important}
.fh-social{display:flex;flex-wrap:wrap;justify-content:center;gap:1.2rem 1.15rem;margin:2rem 0 0;padding:0;list-style:none}
.fh-social a{display:grid;place-items:center;width:2.6rem;height:2.6rem;color:var(--brand-t)}
.fh-social svg{width:2.1rem;height:2.1rem;fill:currentColor}
.fh-cols{display:grid;grid-template-columns:1fr 1fr;gap:2.6rem 1.25rem;margin-top:3.2rem}
.fh-cols h2{font:400 clamp(1.9rem,7vw,2.3rem)/1.1 var(--head-font);color:var(--brand-t);margin:0 0 1.1rem}
.fh-cols ul{list-style:none;margin:0;padding:0}
.fh-cols li{margin:0 0 .85rem;line-height:1.2}
.fh-cols a{font:700 clamp(1.15rem,4.2vw,1.35rem)/1.2 var(--body-font);text-decoration:none}
.fh-cols a:hover{text-decoration:underline}
.fh-wide{grid-column:1/-1}
.fh-legal{display:flex;flex-direction:column;align-items:center;gap:1.1rem;margin:3rem 0 0;padding:0;list-style:none;font-size:1.05rem}
.ftr--heritage .fh-legal a{font:400 1.05rem/1.2 var(--body-font);text-decoration:underline;padding:0}
.fh-copy{margin:2.6rem 0 0;text-align:center;font:400 1.3rem/1.35 var(--head-font)}
.fh-powered{margin:.6rem 0 0;text-align:center;font-size:.92rem}
.fh-powered a{font-weight:700}
@media (min-width:52rem){
  
  .fh-cols{grid-template-columns:repeat(3,1fr);gap:2.5rem}
  .fh-wide{grid-column:auto}
  .fh-legal{flex-direction:row;justify-content:center;gap:2.5rem}
}

/* ---- the apply and sign-in pages, news stories, galleries: plain white pages with big serif titles ---- */
[data-theme=heritage] .hp-head{padding-bottom:1rem}
[data-theme=heritage] .hp-head h1{font-size:clamp(2.4rem,9.5vw,4.2rem);margin-bottom:.35em}
[data-theme=heritage] .hp-lead{max-width:44rem;font:400 clamp(1.3rem,4.4vw,1.65rem)/1.5 var(--head-font);margin:0 0 1rem;color:var(--ink)}
[data-theme=heritage] .hp-note{margin:0 0 1.4rem;font-size:1.02rem;color:#3d3a36}
[data-theme=heritage] .hp-grid{display:grid;gap:3.5rem}
[data-theme=heritage] .hp-form h2,[data-theme=heritage] .hp-next h2{font-size:clamp(1.8rem,6vw,2.3rem);margin-bottom:.3em}
@media (min-width:60rem){[data-theme=heritage] .hp-grid{grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:5rem;align-items:start}}
[data-theme=heritage] .field>span{font:700 .98rem/1.3 var(--body-font)}
[data-theme=heritage] .field input,[data-theme=heritage] .field select,[data-theme=heritage] .field textarea{border:1px solid var(--ink);border-radius:0;min-height:3.2rem;background:#fff}
[data-theme=heritage] .field input:focus,[data-theme=heritage] .field select:focus,[data-theme=heritage] .field textarea:focus{border-color:var(--brand);box-shadow:inset 0 0 0 1px var(--brand)}
[data-theme=heritage] .check input{accent-color:var(--brand)}
[data-theme=heritage] .hp-form .btn--solid{width:100%}
@media (min-width:44rem){[data-theme=heritage] .hp-form .btn--solid{width:auto;min-width:14rem}}
[data-theme=heritage] .hp-tabs{display:flex;gap:1.75rem;margin:0 0 1.5rem;border-bottom:1px solid #cfc6b4}
[data-theme=heritage] .hp-tabs label{position:relative;cursor:pointer}
[data-theme=heritage] .hp-tabs input{position:absolute;opacity:0;inset:0}
[data-theme=heritage] .hp-tabs span{display:block;padding:.7rem 0;margin-bottom:-1px;border-bottom:3px solid transparent;font:700 .95rem/1.2 var(--body-font);letter-spacing:.12em;text-transform:uppercase;color:#57534e}
[data-theme=heritage] .hp-tabs input:checked+span{color:var(--brand-t);border-bottom-color:var(--brand)}
[data-theme=heritage] .hp-tabs input:focus-visible+span{outline:3px solid var(--brand);outline-offset:2px}
[data-theme=heritage] .hp-next{padding-top:.5rem}
[data-theme=heritage] .hp-next ol{list-style:none;margin:0 0 1.5rem;padding:0;counter-reset:n}
[data-theme=heritage] .hp-next li{counter-increment:n;display:grid;grid-template-columns:3rem 1fr;gap:0 .75rem;padding:1.15rem 0;border-top:1px solid #cfc6b4}
[data-theme=heritage] .hp-next li:last-child{border-bottom:1px solid #cfc6b4}
[data-theme=heritage] .hp-next li::before{content:counter(n);grid-row:span 2;font:400 2.4rem/1 var(--head-font);color:var(--brand-t)}
[data-theme=heritage] .hp-next b{font:400 1.45rem/1.2 var(--head-font)}
[data-theme=heritage] .hp-next li span{color:#3d3a36}
[data-theme=heritage] .hp-thanks h2{font-size:clamp(2rem,7vw,2.8rem)}
[data-theme=heritage] .alert{border-radius:0}
[data-theme=heritage] .crumb{color:var(--brand-t);font-weight:700;letter-spacing:.02em}
[data-theme=heritage] .hp-post .tag{display:block;margin-bottom:.9rem}
[data-theme=heritage] .hp-cover{width:100%;max-height:34rem;aspect-ratio:16/9;object-fit:cover;margin:1rem 0 0}
[data-theme=heritage] .event-box{border-left:4px solid var(--brand);background:#f6f2ea;border-radius:0}
[data-theme=heritage] .prose blockquote{border-left:4px solid var(--brand);font-style:italic}
[data-theme=heritage] .share a{border-radius:0;background:none;color:var(--brand-t)}
[data-theme=heritage] .share svg{width:1.6rem;height:1.6rem}
/* galleries */
[data-theme=heritage] .photo{border-radius:0}
[data-theme=heritage] .hp-albums{display:grid;gap:2.4rem 2rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,19rem),1fr))}
[data-theme=heritage] .hp-album{display:flex;flex-direction:column;gap:.5rem;color:var(--ink);text-decoration:none}
[data-theme=heritage] .hp-album img{width:100%;aspect-ratio:16/10;object-fit:cover;margin-bottom:.6rem}
[data-theme=heritage] .hp-album b{font:400 1.7rem/1.12 var(--head-font)}
[data-theme=heritage] .hp-album:hover b{color:var(--brand-t)}
@endverbatim
@verbatim
[data-theme=heritage] .sec--tight{padding-top:1.75rem}
[data-theme=heritage] .hp-head{padding-bottom:0}
[data-theme=heritage] .hp-form form+.hp-note{margin-top:1.75rem}
@endverbatim
@verbatim
/* ---- search: magnifier next to the burger, a dark red box under the header, results in serif ---- */
[data-theme=heritage] .hdr-tools{gap:.1rem;margin-right:-.35rem}
[data-theme=heritage] .sbtn{color:var(--hdr-ink);width:2.75rem;height:2.75rem}
[data-theme=heritage] .sbtn svg{width:1.75rem;height:1.75rem}
[data-theme=heritage] .sbar{background:var(--maroon);border:0;box-shadow:none}
[data-theme=heritage] .sbar-in{padding-top:1.4rem;padding-bottom:1.4rem}
[data-theme=heritage] .sbar input{border:0;border-radius:0;background:#fff;color:var(--ink);font:400 1.35rem/1.2 var(--head-font);min-height:3.4rem}
[data-theme=heritage] .sbar input:focus{box-shadow:inset 0 -4px 0 var(--brand2)}
[data-theme=heritage] .sbar button,[data-theme=heritage] .hp-search button{background:var(--brand2);color:var(--brand2-ink);border-radius:0;font:700 .95rem/1 var(--body-font);letter-spacing:.14em;text-transform:uppercase}
[data-theme=heritage] .sbar button{padding:0 1.5rem}
@media (min-width:60rem){[data-theme=heritage] .hdr-tools{order:3;margin-left:.75rem;margin-right:0}[data-theme=heritage] .menu{order:2;margin-left:auto}}
[data-theme=heritage] .hp-search{display:flex;gap:.75rem;max-width:42rem;margin-top:1.25rem}
[data-theme=heritage] .hp-search input{flex:1;min-width:0;min-height:3.4rem;padding:.6rem 1rem;border:1px solid var(--ink);border-radius:0;background:#fff;color:var(--ink);font:400 1.3rem/1.2 var(--head-font)}
[data-theme=heritage] .hp-search input:focus{outline:0;border-color:var(--brand);box-shadow:inset 0 0 0 1px var(--brand)}
[data-theme=heritage] .hp-search button{min-width:8rem;border:0;cursor:pointer;color:var(--ink)}
[data-theme=heritage] .hp-results{max-width:56rem}
[data-theme=heritage] .result-count{margin:0 0 1.5rem;font-size:1.05rem}
[data-theme=heritage] .result{padding:1.6rem 0;border-color:#cfc6b4}
[data-theme=heritage] .result h2{font:400 clamp(1.6rem,5.5vw,2rem)/1.15 var(--head-font)}
[data-theme=heritage] .result h2 a:hover{color:var(--brand-t);text-decoration:none}
[data-theme=heritage] .result p{color:var(--ink);font-size:1.08rem;line-height:1.6}
[data-theme=heritage] .result mark{background:var(--brand2);color:var(--ink)}
[data-theme=heritage] .result-pic img{aspect-ratio:16/9;border-radius:0}
@endverbatim
