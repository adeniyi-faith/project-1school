@verbatim
/* ============ WARM: white bar with Menu and Apply, full-screen white menu, photo banner, maroon bands with orange headings, maroon footer ============ */
[data-theme=warm]{--hdr-h:4.6rem;--rule:#e4dcd8;--cond:'Barlow Condensed','Barlow',system-ui,sans-serif}
[data-theme=warm],[data-theme=warm] body{line-height:1.75;font-size:1.0625rem}
[data-theme=warm] h1,[data-theme=warm] h2,[data-theme=warm] h3{font-family:var(--head-font);font-weight:600;color:var(--brand);letter-spacing:-.005em;line-height:1.2}
[data-theme=warm] h1{font-size:clamp(2.2rem,7vw,3.9rem)}
[data-theme=warm] h2{font-size:clamp(1.8rem,5vw,2.7rem)}
[data-theme=warm] h3{font-size:1.3rem}
[data-theme=warm] .sec--band h1,[data-theme=warm] .sec--band h2,[data-theme=warm] .sec--dark h1,[data-theme=warm] .sec--dark h2,[data-theme=warm] .sec--dark h3{color:var(--brand2)}
[data-theme=warm] .sec{padding:clamp(3.5rem,8vw,6rem) 0}
[data-theme=warm] .sec--soft{background:var(--soft)}
[data-theme=warm] .sec--band,[data-theme=warm] .sec--dark{background:var(--brand);color:var(--brand-ink)}
[data-theme=warm] .sec--band p{opacity:.95}
[data-theme=warm] .prose p,[data-theme=warm] .sec-head p:not(.eyebrow){font-size:1.1rem;line-height:1.85}
[data-theme=warm] .sec-head h2::after,[data-theme=warm] .sec>.wrap>h2.center::after{content:"";display:block;width:4rem;height:3px;margin:1.1rem 0 0;background:var(--brand2)}
[data-theme=warm] .sec>.wrap>h2.center::after{margin-left:auto;margin-right:auto}
[data-theme=warm] .split img{border-radius:var(--radius-lg)}
[data-theme=warm] .prose blockquote{border-left:3px solid var(--brand2);font-family:var(--head-font);font-style:italic;font-size:1.35rem}
[data-theme=warm] .eyebrow{display:flex;align-items:center;gap:.9rem;margin:0 0 .8rem;font:italic 500 1.15rem/1.3 var(--head-font);letter-spacing:0;text-transform:none;color:var(--link)}
[data-theme=warm] .eyebrow::before{display:none}
[data-theme=warm] .eyebrow::after{content:"";flex:0 0 3.5rem;height:1px;background:currentColor;opacity:.7}
[data-theme=warm] .center .eyebrow{justify-content:center}
[data-theme=warm] .sec--band .eyebrow,[data-theme=warm] .sec--dark .eyebrow{color:var(--brand2)}

/* buttons: outlined in orange, small capitals */
[data-theme=warm] .btn{min-height:3.2rem;padding:0 1.8rem;border:2px solid var(--brand2);border-radius:var(--btn-radius);background:transparent;color:var(--brand);font:600 .82rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase;box-shadow:none;transform:none;transition:background .18s,color .18s}
[data-theme=warm] .btn:hover{background:var(--brand2);color:var(--brand2-ink);box-shadow:none}
[data-theme=warm] .btn--solid{background:transparent;color:var(--brand)}
[data-theme=warm] .sec--band .btn,[data-theme=warm] .cta .btn,[data-theme=warm] .hero .btn,[data-theme=warm] .menu .btn{color:#fff}
[data-theme=warm] .sec--band .btn:hover,[data-theme=warm] .cta .btn:hover,[data-theme=warm] .hero .btn:hover,[data-theme=warm] .menu .btn:hover{color:var(--brand2-ink)}
[data-theme=warm] .menu .btn{color:var(--brand)}

/* header: a plain white bar, a condensed name, Menu and Apply in capitals with orange icons */
[data-theme=warm] .hdr{background:#fff;color:var(--brand);border-bottom:1px solid var(--rule);box-shadow:none}
[data-theme=warm] .hdr-in{min-height:var(--hdr-h);gap:1rem}
[data-theme=warm] .brand{gap:.75rem;color:var(--brand)}
[data-theme=warm] .brand img{height:2.8rem;max-width:3.4rem}
[data-theme=warm] .brand span{font:600 clamp(1.05rem,4vw,1.45rem)/1.05 var(--cond);letter-spacing:.05em;text-transform:uppercase;white-space:normal;overflow:visible;max-width:20rem}
[data-theme=warm] .hdr-tools{gap:.4rem}
[data-theme=warm] .burger,[data-theme=warm] .wa-inq{display:inline-flex;flex-direction:row;align-items:center;gap:.5rem;width:auto;height:auto;padding:.6rem .6rem;border:0;background:none;color:var(--brand);font:600 1rem/1 var(--cond);letter-spacing:.14em;text-transform:uppercase;text-decoration:none;cursor:pointer}
[data-theme=warm] .burger:hover,[data-theme=warm] .wa-inq:hover{color:var(--brand2)}
[data-theme=warm] .wa-dots{width:1.1rem;height:1.1rem;fill:var(--brand2);color:var(--brand2)}
[data-theme=warm] .wa-inq svg{width:1.35rem;height:1.35rem;color:var(--brand2)}
@media (max-width:30rem){[data-theme=warm] .wa-inq span{display:none}}
body.menu-open{overflow:hidden}

/* the menu: a full white sheet on every screen size, big slate links, the open page in maroon */
[data-theme=warm] .menu{inset:0;z-index:60;padding:0;background:#fff;color:var(--text)}
[data-theme=warm] .wa-menu-in{padding-bottom:3rem}
[data-theme=warm] .menu-top{display:flex;align-items:center;justify-content:space-between;min-height:var(--hdr-h);margin-bottom:1rem}
[data-theme=warm] .menu .brand{display:flex}
[data-theme=warm] .menu-x{display:inline-flex;align-items:center;gap:.55rem;padding:.6rem;border:0;background:none;color:var(--brand);font:600 1rem/1 var(--cond);letter-spacing:.14em;text-transform:uppercase;cursor:pointer}
[data-theme=warm] .menu-x .wa-dots{stroke:var(--brand2);fill:none;color:var(--brand2)}
.wa-msearch{display:flex;max-width:34rem;margin:0 0 1.75rem;border-bottom:2px solid var(--brand2)}
.wa-msearch input{flex:1;min-width:0;min-height:3.2rem;padding:.4rem .2rem;border:0;background:none;color:var(--text);font:italic 400 1.3rem/1.2 var(--head-font)}
.wa-msearch input:focus{outline:0}
.wa-msearch button{width:3rem;border:0;background:none;color:var(--brand2);cursor:pointer}
.wa-msearch svg{width:1.5rem;height:1.5rem}
[data-theme=warm] .menu-list{list-style:none;margin:0;padding:0}
[data-theme=warm] .menu .menu-list a{display:block;padding:.55rem 0;border:0;border-radius:0;background:none;opacity:1;color:var(--text);font:500 clamp(1.7rem,6vw,2.6rem)/1.2 var(--head-font);text-decoration:none}
[data-theme=warm] .menu .menu-list a:hover{color:var(--brand)}
[data-theme=warm] .menu .menu-list a[aria-current]{color:var(--brand);text-decoration:none;background:none}
[data-theme=warm] .menu .menu-list a:hover{background:none}
[data-theme=warm] .wa-menu-foot{display:flex;flex-wrap:wrap;align-items:center;gap:1.25rem 2rem;margin-top:2.25rem;padding-top:1.75rem;border-top:1px solid var(--rule)}
[data-theme=warm] .menu .menu-signin{display:inline;border:0;padding:0;color:var(--brand);font:600 .9rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase;text-decoration:underline;text-decoration-color:var(--brand2);text-underline-offset:.4em}
[data-theme=warm] .menu .menu-apply{display:inline-flex;margin:0;width:auto;min-height:3.2rem;padding:0 1.8rem;font-size:.82rem}
@media (min-width:60rem){
  [data-theme=warm] .burger{display:inline-flex}
  [data-theme=warm] .menu{display:none;position:fixed;inset:0;align-items:normal;gap:0;background:#fff;color:var(--text);padding:0;overflow:auto}
  body.menu-open[data-theme=warm] .menu{display:block}
  [data-theme=warm] .menu .menu-signin{margin:0;padding:0;border-radius:0;font-size:.9rem;opacity:1}
}
@media (min-width:60rem){[data-theme=warm] .wa-menu-in{display:grid;grid-template-columns:1fr 1fr;column-gap:4rem;align-content:start}[data-theme=warm] .menu-top,[data-theme=warm] .wa-msearch{grid-column:1/-1}[data-theme=warm] .wa-menu-foot{grid-column:1/-1}}

/* the banner: a full photo under a dark veil, a centred serif headline with one orange word, outlined buttons and a maroon Apply tab */
[data-theme=warm] .hero--warm{display:flex;align-items:center;min-height:min(86svh,46rem);padding:0;color:#fff;text-align:center;background:var(--brand)}
[data-theme=warm] .hero--warm::before{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(30,8,14,.55),rgba(30,8,14,.45) 55%,rgba(30,8,14,.7))}
[data-theme=warm] .hero--warm::after{display:none}
[data-theme=warm] .hero .hero-in{position:relative;display:flex;flex-direction:column;align-items:center;padding-top:clamp(4rem,10vw,7rem);padding-bottom:clamp(5.5rem,12vw,8rem)}
[data-theme=warm] .hero-kick{display:flex;align-items:center;gap:1rem;margin:0 0 1.1rem;font:italic 500 clamp(1.05rem,3.6vw,1.35rem)/1.3 var(--head-font);color:#fff}
[data-theme=warm] .hero-kick::before,[data-theme=warm] .hero-kick::after{content:"";flex:0 0 clamp(1.5rem,6vw,3.5rem);height:1px;background:var(--brand2)}
[data-theme=warm] .hero h1,[data-theme=warm] .hero h2{max-width:16em;margin:0;color:#fff;font:600 clamp(2.3rem,8.4vw,4.6rem)/1.12 var(--head-font);letter-spacing:-.01em;text-shadow:0 2px 28px rgba(0,0,0,.35)}
[data-theme=warm] .hero h1 em,[data-theme=warm] .hero h2 em{color:var(--brand2);font-style:normal}
[data-theme=warm] .hero-sub{max-width:36rem;margin:1.25rem auto 0;font-size:1.15rem;opacity:.95}
[data-theme=warm] .hero .btns{justify-content:center;gap:.8rem;margin-top:2rem}
[data-theme=warm] .hero .btn--solid{background:transparent;box-shadow:none}
.hero-tab{position:absolute;right:0;bottom:clamp(1.5rem,5vw,3rem);display:flex;align-items:center;gap:1rem;padding:.55rem .6rem .55rem 1.4rem;border-radius:2rem 0 0 2rem;background:var(--brand);color:#fff;font:600 .85rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;text-decoration:none;box-shadow:0 12px 28px -12px rgba(0,0,0,.6)}
.hero-tab i,.wa-go{display:grid;place-items:center;flex:none;width:2.8rem;height:2.8rem;border-radius:50%;background:var(--brand2);color:var(--brand2-ink)}
.hero-tab svg,.wa-go svg{width:1.3rem;height:1.3rem}
.hero-tab:hover i{filter:brightness(1.1)}

/* quick links: soft white cards with an orange arrow */
[data-theme=warm] .actions{margin-top:calc(-1 * clamp(2rem,6vw,3.2rem));position:relative;z-index:2;padding-bottom:1rem}
.wa-acts{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),1fr))}
.wa-act{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.3rem 1.3rem 1.3rem 1.6rem;border-radius:var(--radius);background:#fff;color:var(--brand);text-decoration:none;box-shadow:0 18px 40px -22px rgba(60,15,25,.5);border:1px solid var(--rule)}
a.wa-act:hover{transform:translateY(-3px);box-shadow:0 22px 44px -22px rgba(60,15,25,.55)}
.wa-act-text b{display:block;font:600 1.25rem/1.25 var(--head-font)}
.wa-act-text span span{display:block;margin-top:.2rem;color:var(--text);font-size:.95rem;line-height:1.5;font-weight:400}

/* photo cards, numbers and ruled steps */
.wa-tiles{display:grid;gap:1.75rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,17rem),1fr))}
.wa-tile{overflow:hidden;border-radius:var(--radius);background:#fff;border:1px solid var(--rule);box-shadow:0 16px 36px -28px rgba(60,15,25,.5)}
.wa-tile img{width:100%;aspect-ratio:3/2;object-fit:cover}
.wa-tile-body{padding:1.6rem 1.6rem 1.9rem}
[data-theme=warm] .wa-tile h3{margin:0 0 .6rem}
.wa-tile p{margin:0}
.wa-tile .wa-num{margin:0 0 .5rem;font:italic 500 1.05rem/1 var(--head-font);color:var(--link)}
.wa-ico{display:inline-grid;place-items:center;width:3.2rem;height:3.2rem;margin-bottom:1rem;border-radius:50%;background:var(--soft);color:var(--brand)}
.wa-ico .ico{width:1.5rem;height:1.5rem}
.wa-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));gap:2rem 1rem;margin-top:2.25rem;text-align:center}
.wa-stat{padding:.5rem 1rem}
.wa-stat+.wa-stat{border-left:1px solid color-mix(in srgb,var(--brand2) 55%,transparent)}
.wa-stat b{display:block;font:600 clamp(2.4rem,7vw,3.6rem)/1.05 var(--head-font);color:var(--brand2)}
.wa-stat span{display:block;margin-top:.5rem;font:600 .8rem/1.4 var(--body-font);letter-spacing:.18em;text-transform:uppercase}
@media (max-width:40rem){.wa-stat+.wa-stat{border-left:0}}
.wa-steps{list-style:none;margin:0;padding:0;counter-reset:s}
.wa-steps li{display:grid;grid-template-columns:4.2rem 1fr;gap:1rem;padding:1.6rem 0;border-top:1px solid var(--brand2)}
.wa-steps li:last-child{border-bottom:1px solid var(--brand2)}
.wa-step-n{font:italic 500 2.2rem/1 var(--head-font);color:var(--brand2)}
[data-theme=warm] .wa-steps h3{margin:0 0 .35rem}
.wa-steps p{margin:0}
.wa-stories{display:grid;gap:2.5rem 2rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,18rem),1fr))}
.wa-story img{width:100%;aspect-ratio:4/3;object-fit:cover;margin-bottom:1.25rem;border-radius:var(--radius)}
[data-theme=warm] .wa-story h3{margin:0 0 .6rem}
.wa-story h3 a{color:inherit;text-decoration:none}
.wa-story h3 a:hover{text-decoration:underline;text-decoration-color:var(--brand2)}
.wa-story p{margin:0 0 .5rem}
.wa-story .date{color:var(--muted);font:600 .75rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase}
.wa-more{margin:2.75rem 0 0;text-align:center}
.wa-acc{border-top:1px solid var(--rule)}
.wa-acc details,.wa-acc .wa-acc-row{border-bottom:1px solid var(--rule)}
.wa-acc summary{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.3rem 0;font:500 1.2rem/1.4 var(--head-font);color:var(--brand);cursor:pointer;list-style:none}
.wa-acc summary::-webkit-details-marker{display:none}
.wa-acc summary::after{content:"+";flex:none;display:grid;place-items:center;width:2rem;height:2rem;border-radius:50%;background:var(--brand2);color:var(--brand2-ink);font:500 1.3rem/1 var(--body-font)}
.wa-acc details[open] summary::after{content:"\2212"}
.wa-acc details p{margin:0;padding:0 0 1.5rem;max-width:46rem}
.wa-acc .wa-acc-row{padding:1.2rem 0;font:500 1.2rem/1.4 var(--head-font);color:var(--brand)}

/* closing band and contact */
[data-theme=warm] .cta{background:var(--brand);color:var(--brand-ink);padding:clamp(4rem,9vw,6.5rem) 0;text-align:center}
[data-theme=warm] .cta h1,[data-theme=warm] .cta h2{max-width:22ch;margin:0 auto 1.1rem;color:var(--brand2);font-size:clamp(2rem,6vw,3.2rem)}
[data-theme=warm] .cta p{max-width:36rem;margin:0 auto 1.75rem;opacity:.95}
[data-theme=warm] .cta .btns{justify-content:center}
.wa-contact{display:grid;gap:2rem;margin-top:2.25rem;text-align:center;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr))}
.wa-contact p{margin:0;font:italic 500 1.05rem/1.4 var(--head-font);color:var(--brand2)}
.wa-contact b{display:block;margin-top:.3rem;font:500 1.25rem/1.4 var(--body-font);color:#fff}
.wa-contact a{color:inherit;text-decoration:none}
[data-theme=warm] .sec--band h2.center{text-align:center}

/* the quote: a rounded bracket with the school's round logo on top */
[data-theme=warm] .sec--quote{padding-top:clamp(4rem,9vw,6rem)}
.wa-quote{position:relative;max-width:48rem;margin:0 auto;padding:3.6rem clamp(1.5rem,6vw,3.5rem) 2.5rem;border-left:3px solid var(--brand2);border-right:3px solid var(--brand2);border-radius:2.75rem;text-align:center}
.wa-quote-logo{position:absolute;top:-2.4rem;left:50%;translate:-50% 0;display:grid;place-items:center;width:4.8rem;height:4.8rem;border-radius:50%;background:#fff;border:3px solid var(--brand2);overflow:hidden}
.wa-quote-logo img{width:70%;height:70%;object-fit:contain}
.wa-quote-mark{font:700 3.4rem/1 var(--head-font);color:var(--brand);padding-top:.7rem}
.wa-quote blockquote{margin:0}
.wa-quote blockquote p{margin:0;font:italic 400 clamp(1.45rem,4.4vw,2.1rem)/1.45 var(--head-font);color:var(--brand)}
.wa-quote figcaption{margin-top:1.25rem;font:600 .8rem/1.4 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--text)}

/* photo wall: rounded tiles */
[data-theme=warm] .photos{columns:auto;display:grid;grid-template-columns:repeat(2,1fr);gap:.6rem}
[data-theme=warm] .photo{margin:0;border-radius:var(--radius);aspect-ratio:1;overflow:hidden}
[data-theme=warm] .photo img{height:100%;object-fit:cover}
@media (min-width:44rem){[data-theme=warm] .photos{grid-template-columns:repeat(3,1fr);gap:.9rem}}
@media (min-width:64rem){[data-theme=warm] .photos{grid-template-columns:repeat(4,1fr)}}

/* footer: maroon, a faint watermark, a centred serif address, orange social circles and a round up arrow */
.ftr--warm{border-top:1px solid rgba(255,255,255,.14);position:relative;overflow:hidden;margin:0;padding:clamp(3.5rem,8vw,5.5rem) 0 2.25rem;border-radius:0;background:var(--brand);color:var(--brand-ink);text-align:center}
.wf-mark{position:absolute;left:50%;top:50%;width:min(36rem,92vw);height:auto;translate:-50% -50%;opacity:.07;filter:brightness(0) invert(1);pointer-events:none}
.wf-in{position:relative}
.wf-name{display:inline-block;font:600 clamp(1.7rem,6vw,2.5rem)/1.1 var(--cond);letter-spacing:.06em;text-transform:uppercase;color:inherit;text-decoration:none}
.wf-addr{margin:1.25rem auto 0;max-width:30rem;font:400 1.2rem/1.7 var(--head-font);font-style:normal}
.wf-addr a{color:inherit;text-decoration:none}
.wf-addr a:hover{text-decoration:underline}
.wf-links{display:flex;flex-wrap:wrap;justify-content:center;gap:.5rem 1.6rem;margin:1.75rem 0 0;padding:0;list-style:none}
.wf-links a{color:inherit;font:600 .8rem/1.4 var(--body-font);letter-spacing:.16em;text-transform:uppercase;text-decoration:underline;text-decoration-color:var(--brand2);text-underline-offset:.4em}
.wf-icons{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:.9rem;margin:2.25rem 0 0}
.ftr--warm .social{justify-content:center;flex-wrap:wrap;margin:0;gap:.7rem}
.ftr--warm .social a{width:2.9rem;height:2.9rem;background:var(--brand2);color:var(--brand2-ink)}
.ftr--warm .social a:hover{filter:brightness(1.1)}
.wf-up{display:grid;place-items:center;width:2.9rem;height:2.9rem;border:2px solid var(--brand2);border-radius:50%;color:var(--brand2)}
.wf-up svg{width:1.3rem;height:1.3rem}
.wf-up:hover{background:var(--brand2);color:var(--brand2-ink)}
.wf-legal{margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,.18);font-size:.9rem;line-height:1.8;opacity:.9}
.wf-legal p{margin:0}
.wf-legal a{color:inherit}

/* inner pages: a maroon title band with an orange rule, then plain content */
.wa-head{background:var(--brand);color:var(--brand-ink);padding:clamp(3rem,8vw,5rem) 0 clamp(2.75rem,7vw,4.25rem)}
[data-theme=warm] .wa-head h1{max-width:20ch;margin:0;color:#fff}
.wa-kick{display:flex;align-items:center;gap:.9rem;margin:0 0 1rem;font:italic 500 1.2rem/1.3 var(--head-font);color:var(--brand2)}
.wa-kick::after{content:"";flex:0 0 3.5rem;height:1px;background:currentColor}
.wa-lead{max-width:40rem;margin:1.1rem 0 0;font-size:1.2rem;line-height:1.65;opacity:.95}
.wa-meta{margin:1.3rem 0 0;font:600 .78rem/1.5 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--brand2)}
.wa-back{display:inline-block;margin-bottom:1.5rem;color:var(--brand2);font:600 .76rem/1 var(--body-font);letter-spacing:.2em;text-decoration:none;text-transform:uppercase}
.wa-body{padding:clamp(2.5rem,6vw,4rem) 0 clamp(4rem,9vw,6.5rem)}
.wa-body--soft{background:var(--soft)}
.wa-narrow{max-width:50rem}
.wa-cover{width:100%;aspect-ratio:16/9;object-fit:cover;margin:0 0 2.5rem;border-radius:var(--radius-lg)}
[data-theme=warm] .wa-h2{margin:0 0 1.5rem}
.wa-tabs{display:flex;flex-wrap:wrap;gap:.5rem;margin:0 0 2.25rem}
.wa-tabs a{padding:.75rem 1.15rem;border:2px solid var(--brand2);border-radius:var(--btn-radius);color:var(--brand);font:600 .76rem/1 var(--body-font);letter-spacing:.16em;text-decoration:none;text-transform:uppercase}
.wa-tabs a:hover{background:color-mix(in srgb,var(--brand2) 14%,#fff)}
.wa-tabs a[aria-current]{background:var(--brand);border-color:var(--brand);color:var(--brand-ink)}
.wa-cards{display:grid;gap:2rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,19rem),1fr))}
.wa-card{overflow:hidden;display:flex;flex-direction:column;border-radius:var(--radius);background:#fff;border:1px solid var(--rule);box-shadow:0 16px 36px -28px rgba(60,15,25,.5)}
.wa-card img{width:100%;aspect-ratio:3/2;object-fit:cover}
.wa-card-body{padding:1.5rem 1.5rem 1.75rem}
.wa-card .wa-kick{margin-bottom:.6rem;font-size:1rem;color:var(--link)}
.wa-card .wa-kick::after{display:none}
[data-theme=warm] .wa-card h2{margin:0 0 .6rem;font-size:1.35rem}
.wa-card h2 a{color:inherit;text-decoration:none}
.wa-card h2 a:hover{text-decoration:underline;text-decoration-color:var(--brand2)}
.wa-card p{margin:0 0 .7rem;font-size:1rem}
.wa-card time{color:var(--muted);font:600 .74rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase}
.wa-albums{display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr))}
.wa-album{display:block;overflow:hidden;border-radius:var(--radius);background:#fff;border:1px solid var(--rule);color:inherit;text-decoration:none}
.wa-album img,.wa-album-blank{display:block;width:100%;aspect-ratio:1;object-fit:cover;background:var(--brand)}
.wa-album-cap{display:block;padding:1rem 1.2rem 1.2rem}
.wa-album-cap small{display:block;margin-bottom:.25rem;color:var(--muted);font:600 .7rem/1.3 var(--body-font);letter-spacing:.16em;text-transform:uppercase}
.wa-album-cap b{display:block;font:600 1.1rem/1.3 var(--head-font);color:var(--brand)}
.wa-album:hover{border-color:var(--brand2)}
.wa-list{list-style:none;margin:0;padding:0;border-top:1px solid var(--rule)}
.wa-list li{border-bottom:1px solid var(--rule)}
.wa-list a{display:block;padding:1.1rem 0;font:500 1.15rem/1.4 var(--head-font);color:var(--brand);text-decoration:none}
.wa-list a:hover{color:var(--brand2)}
.wa-list span{display:block;margin-bottom:.2rem;font:italic 500 .95rem/1.3 var(--head-font);color:var(--text)}
[data-theme=warm] .wa-kick--dark{color:var(--link)}

/* forms and the side panel */
.wa-split{display:grid;gap:3rem;align-items:start}
@media (min-width:56rem){.wa-split{grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:4rem}}
.wa-panel{padding:2rem 1.9rem;border-radius:var(--radius-lg);background:var(--soft);border-top:4px solid var(--brand2)}
[data-theme=warm] .wa-panel h2{margin:0 0 1rem;font-size:1.7rem}
.wa-rows{list-style:none;margin:0;padding:0}
.wa-rows li+li{border-top:1px solid var(--rule)}
.wa-rows div{padding:1rem 0}
.wa-rows b{display:block;font:600 1.15rem/1.35 var(--head-font);color:var(--brand)}
.wa-rows span{display:block;margin-top:.2rem;font-size:.97rem}
.wa-note{margin:0 0 1.5rem;color:var(--muted)}
.wa-field{display:block;margin:0 0 1.25rem}
.wa-field>span{display:block;margin-bottom:.45rem;font:600 .74rem/1.3 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--brand)}
.wa-field input,.wa-field textarea{display:block;width:100%;min-height:3.3rem;padding:.7rem 1rem;border:1px solid #cdc3be;border-radius:.6rem;background:#fff;color:var(--text);font:inherit;font-size:1rem}
.wa-field textarea{min-height:8rem;resize:vertical}
.wa-field input:focus,.wa-field textarea:focus{outline:0;border-color:var(--brand2);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand2) 25%,transparent)}
.wa-field .err{display:block;margin-top:.35rem;color:#b91c1c;font-size:.9rem;font-weight:600}
.wa-two{display:grid;gap:0 1.25rem}
@media (min-width:40rem){.wa-two{grid-template-columns:1fr 1fr}}
.wa-fine{margin:1.25rem 0 0;color:var(--muted);font-size:.92rem}
.wa-who{display:grid;grid-template-columns:repeat(3,1fr);margin:0 0 1.25rem;border:2px solid var(--brand);border-radius:.6rem;overflow:hidden}
.wa-who label{position:relative;display:block;cursor:pointer}
.wa-who input{position:absolute;inset:0;opacity:0}
.wa-who span{display:block;padding:.9rem .4rem;text-align:center;font:600 .74rem/1.2 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--brand)}
.wa-who label+label{border-left:2px solid var(--brand)}
.wa-who input:checked+span{background:var(--brand);color:var(--brand-ink)}
.wa-who input:focus-visible+span{outline:3px solid var(--brand2);outline-offset:-3px}
.wa-thanks{padding:1rem 0}
.wa-search{display:flex;flex-wrap:wrap;gap:.6rem;margin:0 0 2rem}
.wa-search input{flex:1 1 14rem;min-height:3.4rem;padding:.7rem 1rem;border:1px solid #cdc3be;border-radius:.6rem;font:italic 400 1.15rem/1.2 var(--head-font)}
.wa-search input:focus{outline:0;border-color:var(--brand2)}
.wa-results .result{border-color:var(--rule)}
[data-theme=warm] .wa-results .result h2{margin:.2rem 0 .4rem;font-size:1.25rem}
.wa-results .tag{color:var(--link)}
@endverbatim
