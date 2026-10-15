@verbatim
/* ============ EVERGREEN: three-part green bar, green full-screen menu, photo over a green headline panel, cream pages, square capital buttons ============ */
[data-theme=evergreen]{--hdr-h:4.25rem;--rule:rgba(20,40,30,.2)}
[data-theme=evergreen],[data-theme=evergreen] body{line-height:1.7;font-size:1.0625rem}
[data-theme=evergreen] h1,[data-theme=evergreen] h2,[data-theme=evergreen] h3{font-family:var(--head-font);font-weight:400;color:var(--brand-t);letter-spacing:-.01em;line-height:1.15}
[data-theme=evergreen] h1{font-size:clamp(2.4rem,8.5vw,4.4rem)}
[data-theme=evergreen] h2{font-size:clamp(2rem,6vw,3rem)}
[data-theme=evergreen] h3{font-size:1.45rem}
[data-theme=evergreen] .sec--band h1,[data-theme=evergreen] .sec--band h2,[data-theme=evergreen] .sec--band h3{color:var(--brand-ink)}
[data-theme=evergreen] .sec--dark h1,[data-theme=evergreen] .sec--dark h2,[data-theme=evergreen] .sec--dark h3{color:#fff}
[data-theme=evergreen] .sec{padding:clamp(3.5rem,9vw,6.5rem) 0}
[data-theme=evergreen] .sec--soft{background:#fff}
[data-theme=evergreen] .sec--band{background:var(--brand);color:var(--brand-ink)}
[data-theme=evergreen] .sec--dark{background:var(--deep);color:#fff}
[data-theme=evergreen] .prose p,[data-theme=evergreen] .sec-head p:not(.eyebrow){font-size:1.125rem;line-height:1.8}
[data-theme=evergreen] em,[data-theme=evergreen] .accent{font-style:italic;text-decoration:underline;text-decoration-thickness:1px;text-underline-offset:.2em}
[data-theme=evergreen] .eyebrow{margin:0 0 1rem;font:600 .8rem/1.3 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:color-mix(in srgb,var(--brand2-t) 40%,var(--brand-t))}
[data-theme=evergreen] .eyebrow::before{display:none}
[data-theme=evergreen] .sec--band .eyebrow{color:inherit;opacity:.9}
[data-theme=evergreen] .sec--dark .eyebrow{color:var(--brand2-d)}
[data-theme=evergreen] .split img{border-radius:0}
[data-theme=evergreen] .prose blockquote{border-left:2px solid var(--brand2);font-family:var(--head-font);font-style:italic;font-size:1.35rem}

/* buttons: square, small wide capitals */
[data-theme=evergreen] .btn{min-height:3.25rem;padding:0 1.9rem;border:1px solid var(--brand);border-radius:0;background:var(--brand);color:var(--brand-ink);font:600 .8rem/1 var(--body-font);letter-spacing:.18em;text-transform:uppercase;box-shadow:none;transform:none;transition:background .18s,color .18s}
[data-theme=evergreen] .btn:hover{background:var(--brand2);border-color:var(--brand2);color:var(--brand2-ink);box-shadow:none}
[data-theme=evergreen] .btn--ghost{background:transparent;color:var(--brand-t)}
[data-theme=evergreen] .sec--band .btn{background:transparent;border-color:currentColor;color:var(--brand-ink)}
[data-theme=evergreen] .sec--band .btn:hover{background:var(--brand-ink);color:var(--brand)}
[data-theme=evergreen] .sec--dark .btn,[data-theme=evergreen] .cta .btn,[data-theme=evergreen] .eg-hero-panel .btn{background:transparent;border-color:#fff;color:#fff}
[data-theme=evergreen] .sec--dark .btn:hover,[data-theme=evergreen] .cta .btn:hover,[data-theme=evergreen] .eg-hero-panel .btn:hover{background:#fff;color:var(--deep)}
[data-theme=evergreen] .eg-hero-panel .btn--solid,[data-theme=evergreen] .cta .btn--solid{background:#fff;color:var(--deep)}
[data-theme=evergreen] .eg-hero-panel .btn--solid:hover,[data-theme=evergreen] .cta .btn--solid:hover{background:transparent;color:#fff}

/* header: three cells with hairlines between them: menu, centred name, search */
[data-theme=evergreen] .hdr{background:var(--hdr-bg);color:var(--hdr-ink);box-shadow:none;border-bottom:1px solid color-mix(in srgb,var(--hdr-ink) 25%,transparent)}
.eg-bar{display:grid;grid-template-columns:4.25rem minmax(0,1fr) 4.25rem;align-items:stretch;min-height:var(--hdr-h)}
.eg-bar .burger,.eg-bar .sbtn{width:100%;height:auto;min-height:var(--hdr-h);padding:0;display:grid;place-items:center;align-content:center;gap:.28rem;color:var(--hdr-ink)}
.eg-bar .burger{border-right:1px solid color-mix(in srgb,var(--hdr-ink) 25%,transparent)}
.eg-bar .sbtn{border-left:1px solid color-mix(in srgb,var(--hdr-ink) 25%,transparent)}
.eg-bar .burger i{width:1.5rem;height:2px}
.eg-bar .brand{justify-content:center;gap:.7rem;padding:.4rem .75rem;color:var(--hdr-name)}
.eg-bar .brand img{height:2.5rem}
.eg-bar .brand span{font:400 clamp(1.15rem,5vw,1.55rem)/1.1 var(--head-font);letter-spacing:.01em;text-align:center;white-space:normal;max-width:100%}
@media (min-width:60rem){.eg-bar{grid-template-columns:5.5rem minmax(0,1fr) 5.5rem}}

/* the menu: a deep green sheet under the bar, capitals with arrows, small links and round social icons */
[data-theme=evergreen] .menu{inset:var(--hdr-h) 0 0 0;padding:0;background:var(--deep);color:#fff;z-index:60}
.eg-menu-in{max-width:44rem;margin:0 auto;padding:1.25rem 1.5rem 3.5rem}
[data-theme=evergreen] .menu-list{list-style:none;margin:0;padding:0}
[data-theme=evergreen] .menu .menu-list a{display:flex;align-items:center;justify-content:space-between;padding:1.15rem 0;border-bottom:1px solid rgba(255,255,255,.22);font:600 .95rem/1.2 var(--body-font);letter-spacing:.16em;text-transform:uppercase}
[data-theme=evergreen] .menu .menu-list a svg{width:1.1rem;height:1.1rem;flex:none}
[data-theme=evergreen] .menu .menu-list a[aria-current]{text-decoration:none;color:var(--brand2-d)}
[data-theme=evergreen] .menu .menu-list a:hover{color:var(--brand2-d)}
.eg-menu-small{list-style:none;margin:2rem 0 0;padding:1.5rem 0 0;display:grid;gap:.9rem}
[data-theme=evergreen] .menu .eg-menu-small a{display:inline;padding:0;border:0;font:500 .98rem/1.3 var(--body-font);opacity:.92}
[data-theme=evergreen] .menu .eg-menu-small a:hover{text-decoration:underline;text-underline-offset:.25em}
.eg-menu-social{margin-top:1.75rem}
.eg-menu-social li a{background:rgba(255,255,255,.14);border:0;padding:0}
@media (min-width:60rem){[data-theme=evergreen] .menu{display:none;position:fixed;background:var(--deep);color:#fff;padding:0;overflow:auto;align-items:normal}body.menu-open[data-theme=evergreen] .menu{display:block}}

/* search: a cream panel dropping from the bar, a Keyword line and a green button */
[data-theme=evergreen] .hdr .sbar{background:var(--bg);color:var(--text);border-top:0;box-shadow:0 22px 34px -18px rgba(0,0,0,.45)}
.eg-search{max-width:40rem;margin:0 auto;padding:2rem 1.5rem 2.25rem;display:grid;gap:.5rem}
.eg-search label{font:600 .78rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--brand-t)}
.eg-search input{min-height:3.4rem;border:0;border-bottom:2px solid var(--brand);background:transparent;color:var(--text);font:400 1.4rem/1.2 var(--head-font);padding:.4rem 0;border-radius:0}
.eg-search input:focus{outline:0;border-bottom-color:var(--brand2)}
.eg-search .btn{justify-self:start;margin-top:1rem}

/* the banner: the school photo on top, then a green panel with a big serif headline */
[data-theme=evergreen] .hero--evergreen{display:block;min-height:0;padding:0;background:var(--brand);color:#fff}
[data-theme=evergreen] .hero--evergreen .hero-img{position:static;display:block;width:100%;height:auto;aspect-ratio:4/3;max-height:34rem;object-fit:cover}
[data-theme=evergreen] .hero--evergreen::before,[data-theme=evergreen] .hero--evergreen::after{display:none}
.eg-hero-panel{background:var(--deep);color:#fff}
.eg-hero-panel .wrap{padding-top:clamp(2.25rem,6vw,4rem);padding-bottom:clamp(2.75rem,7vw,4.5rem)}
.eg-kick{margin:0 0 1rem;font:600 .78rem/1.3 var(--body-font);letter-spacing:.22em;text-transform:uppercase;color:var(--brand2-d)}
[data-theme=evergreen] .eg-hero-panel h1,[data-theme=evergreen] .eg-hero-panel h2{color:#fff;max-width:14ch;font-size:clamp(2.7rem,11vw,5.2rem);letter-spacing:-.015em;text-shadow:none}
[data-theme=evergreen] .eg-hero-panel .hero-sub{margin:1.25rem 0 1.75rem;font-size:1.1rem;max-width:32rem;opacity:.92}
@media (min-width:60rem){
  [data-theme=evergreen] .hero--evergreen{display:grid;grid-template-columns:1.1fr 1fr;min-height:36rem}
  [data-theme=evergreen] .hero--evergreen .hero-img{height:100%;max-height:none;aspect-ratio:auto}
  .eg-hero-panel{display:flex;align-items:center}
}

/* cards: flat cream or white panels with a hairline, big serif titles */
[data-theme=evergreen] .card,[data-theme=evergreen] .news-item,[data-theme=evergreen] .steps li{border:1px solid var(--rule);border-radius:0;background:#fff;box-shadow:none}
[data-theme=evergreen] .sec--soft .card,[data-theme=evergreen] .sec--soft .news-item{background:var(--bg)}
[data-theme=evergreen] .card:hover{transform:none;box-shadow:none;border-color:var(--brand)}
[data-theme=evergreen] .card-body{padding:1.5rem}
[data-theme=evergreen] .card h3,[data-theme=evergreen] .news-item h3{font-size:1.5rem;color:var(--brand-t)}
[data-theme=evergreen] .news-item img,[data-theme=evergreen] .card img{aspect-ratio:3/2}
[data-theme=evergreen] .cta{background:var(--deep);color:#fff}
[data-theme=evergreen] .cta h1,[data-theme=evergreen] .cta h2{color:#fff}
[data-theme=evergreen] .stat b,[data-theme=evergreen] .stats strong{font-family:var(--head-font);font-weight:400}

/* quick actions: flat ruled rows under the banner, not floating cards */
[data-theme=evergreen] .actions{margin-top:0;padding:clamp(1.5rem,4vw,2.5rem) 0 0}
[data-theme=evergreen] .actions-grid{gap:0;border-top:1px solid var(--rule)}
[data-theme=evergreen] .action{border-radius:0;box-shadow:none;background:transparent;padding:1.35rem 0;border-bottom:1px solid var(--rule);outline:0}
[data-theme=evergreen] .action-ico,[data-theme=evergreen] .card-ico{border-radius:50%;background:var(--band);color:#fff}
[data-theme=evergreen] .action-text b{font:400 1.45rem/1.2 var(--head-font);color:var(--brand-t)}
@media (min-width:60rem){[data-theme=evergreen] .actions-grid{grid-template-columns:repeat(3,1fr);column-gap:2rem}}

/* footer: big name, tagline, address, bright phone, ruled rows with chevrons, darker legal bar */
.ftr--evergreen{background:var(--deep);color:#fff;padding:0}
.eg-f{padding-top:clamp(3rem,8vw,5rem);padding-bottom:2.5rem}
.eg-f-name{display:block;font:400 clamp(2.2rem,9vw,3.6rem)/1.05 var(--head-font);color:inherit;text-decoration:none}
.eg-f-tag{margin:.9rem 0 0;font:italic 400 1.15rem/1.4 var(--head-font);opacity:.9}
.eg-f-addr{margin:1.5rem 0 2rem;font-style:normal;opacity:.95;line-height:1.7}
.eg-f-addr a{color:var(--brand2-d);font-weight:600;text-decoration:none}
.eg-f-addr a:hover{text-decoration:underline}
.eg-f-links{list-style:none;margin:0 0 1.5rem;padding:0;border-top:1px solid rgba(255,255,255,.22)}
.ftr--evergreen .eg-f-links li{margin:0}
.ftr--evergreen .eg-f-links li a{font-weight:400;text-decoration:none;display:flex;justify-content:space-between;align-items:center;padding:1rem 0;border-bottom:1px solid rgba(255,255,255,.22);color:inherit;text-decoration:none;font:400 1.35rem/1.2 var(--head-font)}
.eg-f-links li a svg{width:1.1rem;height:1.1rem}
.eg-f-links li a:hover{color:var(--brand2-d)}
.ftr--evergreen .social{margin:1.75rem 0 0}
.ftr--evergreen .social li{margin:0}
.ftr--evergreen .social li a{display:grid;place-items:center;width:2.75rem;height:2.75rem;padding:0;background:rgba(255,255,255,.14)}
.eg-menu-social li a{display:grid;place-items:center;width:2.75rem;height:2.75rem}
.eg-f-legal{background:rgba(0,0,0,.28);padding:1.25rem 0;font-size:.88rem}
.eg-f-legal p{margin:.25rem 0;opacity:.85}
.eg-f-legal a{color:inherit}
@media (min-width:60rem){.eg-f-links{columns:2;column-gap:3rem}.eg-f-links li{break-inside:avoid}}

/* inner pages and forms follow the same flat look */
[data-theme=evergreen] input,[data-theme=evergreen] select,[data-theme=evergreen] textarea{border-radius:0}
[data-theme=evergreen] .page-head{background:var(--deep);color:#fff}
[data-theme=evergreen] .page-head h1,[data-theme=evergreen] .page-head h2{color:#fff}
[data-theme=evergreen] .page-head .eyebrow,[data-theme=evergreen] .page-head .tag{color:var(--brand2-d)}
[data-theme=evergreen] .page-head a{color:#fff}
[data-theme=evergreen] .pagehero{background:var(--deep);color:#fff}
[data-theme=evergreen] .pagehero h1{color:#fff}

/* testimonials: a white card with a round green seal on its top edge, centred serif words, green capital name */
[data-theme=evergreen] .tcard{position:relative;margin-top:2rem;padding-top:3.5rem;border:0;border-radius:0;background:#fff;text-align:center;align-items:center}
[data-theme=evergreen] .tcard::before{content:"\201C";position:absolute;top:-2rem;left:50%;transform:translateX(-50%);display:grid;place-items:center;width:4rem;height:4rem;padding-top:.7rem;border-radius:50%;background:var(--brand);color:var(--brand-ink);font:400 3rem/1 var(--head-font)}
[data-theme=evergreen] .sec--soft .tcard{background:var(--bg)}
[data-theme=evergreen] .tcard blockquote{font-style:normal;font-size:clamp(1.2rem,3.4vw,1.5rem);line-height:1.55}
[data-theme=evergreen] .tcard figcaption{flex-direction:column;text-align:center;margin-top:auto}
[data-theme=evergreen] .tcard-who{align-items:center}
[data-theme=evergreen] .tcard-who b{font:600 .8rem/1.3 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--brand-t)}
[data-theme=evergreen] .sec--dark .tcard{background:#fff;color:var(--text)}
[data-theme=evergreen] .sec--dark .tcard-who span{color:var(--muted);opacity:1}
@endverbatim
