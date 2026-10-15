@verbatim
*,*::before,*::after{box-sizing:border-box}
html{-webkit-text-size-adjust:100%;scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--text);font-family:var(--body-font);font-size:1.0625rem;line-height:1.65;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility}
img{max-width:100%;height:auto;display:block}
a{color:var(--link);text-underline-offset:.2em}
h1,h2,h3{font-family:var(--head-font);font-weight:400;line-height:1.15;margin:0 0 .6em;letter-spacing:-.01em;text-wrap:balance}
h1{font-size:clamp(2.1rem,6.5vw,3.6rem)}
h2{font-size:clamp(1.7rem,4.6vw,2.6rem)}
h3{font-size:1.35rem}
p{margin:0 0 1em;text-wrap:pretty}
.wrap{width:100%;max-width:72rem;margin:0 auto;padding:0 1.25rem}
.sr{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.skip{position:absolute;left:-999px;top:0;background:var(--brand);color:var(--brand-ink);padding:.75rem 1rem;z-index:100}
.skip:focus{left:0}
:focus-visible{outline:3px solid var(--brand);outline-offset:3px;border-radius:4px}

/* buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;min-height:3rem;padding:.7rem 1.6rem;border-radius:var(--btn-radius);font:600 .95rem/1.2 var(--body-font);text-transform:var(--btn-case);letter-spacing:var(--btn-track);text-decoration:none;border:2px solid transparent;cursor:pointer;transition:transform .15s,background .15s,color .15s,box-shadow .15s}
.btn:hover{transform:translateY(-1px)}
.btn--solid{background:var(--brand);color:var(--brand-ink)}
.btn--solid:hover{box-shadow:0 6px 18px -6px var(--brand)}
.btn--ghost{border-color:currentColor;background:transparent}
.btns{display:flex;flex-wrap:wrap;gap:.75rem}

/* header */
.hdr{position:sticky;top:0;z-index:50;box-shadow:0 1px 0 var(--line),0 8px 24px -18px rgba(0,0,0,.35);background:var(--hdr-bg);color:var(--hdr-ink);border-bottom:1px solid var(--line)}
.hdr-in{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:4.25rem}
.brand{display:flex;align-items:center;gap:.7rem;color:var(--hdr-name);text-decoration:none;min-width:0}
.brand img{height:2.6rem;width:auto;max-width:3.4rem;object-fit:contain}
.brand span{font:400 1.3rem/1.1 var(--head-font);letter-spacing:-.01em;overflow:hidden;text-overflow:ellipsis}
.burger{display:inline-flex;flex-direction:column;justify-content:center;gap:.3rem;width:3rem;height:3rem;padding:.8rem;background:none;border:0;color:var(--hdr-ink);cursor:pointer}
.burger i{display:block;height:2px;background:currentColor;border-radius:2px;transition:transform .2s,opacity .2s}
.menu-open .burger i:nth-child(1){transform:translateY(.4rem) rotate(45deg)}
.menu-open .burger i:nth-child(2){opacity:0}
.menu-open .burger i:nth-child(3){transform:translateY(-.4rem) rotate(-45deg)}
.menu{display:none;position:fixed;inset:var(--hdr-h,4.25rem) 0 0 0;background:var(--brand);color:var(--brand-ink);padding:1.5rem 1.5rem 3rem;overflow:auto;z-index:40}
.menu-open .menu{display:block}
.menu a{display:block;color:inherit;text-decoration:none;font:400 1.9rem/1.2 var(--head-font);padding:.85rem 0;border-bottom:1px solid rgba(255,255,255,.18)}
.menu a[aria-current]{text-decoration:underline;text-underline-offset:.3em}
.menu .btn{margin-top:1.5rem;width:100%;font-size:1rem;border-color:currentColor;color:inherit}
body.menu-open{overflow:hidden}
@media (min-width:72rem){
  .burger{display:none}
  .menu,.menu-open .menu{display:flex;align-items:center;gap:.25rem;position:static;background:none;color:inherit;padding:0;overflow:visible}
  .menu a{font:600 .95rem/1 var(--body-font);padding:.7rem .85rem;border:0;border-radius:999px;opacity:.9}
  .menu a:hover{opacity:1;background:rgba(0,0,0,.06)}
  .menu a[aria-current]{opacity:1;background:rgba(0,0,0,.08);text-decoration:none}
  .menu-signin{margin-left:.25rem}
  .menu .btn{margin:0 0 0 .5rem;width:auto;min-height:2.5rem;padding:.5rem 1.2rem;font-size:.85rem;background:var(--brand);border-color:var(--brand);color:var(--brand-ink)}
}

/* the bar never wraps its words: items stay on one line, the bar gets more room, and when it is still tight the spacing shrinks (see nav-tight in layout) */
.hdr .menu a,.hdr .menu .btn,.hdr .menu-signin{white-space:nowrap}
@media (min-width:72rem){
  .hdr .wrap.hdr-in{max-width:92rem;padding-left:clamp(1.25rem,3vw,2.5rem);padding-right:clamp(1.25rem,3vw,2.5rem)}
  .hdr .brand{flex:0 1 auto;min-width:0}
  .hdr .brand span{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
  .hdr .menu{flex:none}
  body.nav-tight .hdr .menu a,body.nav-tight .hdr .menu .menu-list a,body.nav-tight .hdr .menu-main a{padding-left:.4rem!important;padding-right:.4rem!important;letter-spacing:.04em!important}
  body.nav-tight .hdr .menu .btn,body.nav-tight .hdr .menu .menu-apply{padding-left:.8rem!important;padding-right:.8rem!important;margin-left:.25rem!important;letter-spacing:.04em!important}
}

/* site search: the icon in the header and the box that drops down under it (every theme) */
.hdr-tools{display:flex;align-items:center;gap:.15rem}
.sbtn{display:inline-grid;place-items:center;width:3rem;height:3rem;color:var(--hdr-ink);text-decoration:none}
.sbtn svg{width:1.55rem;height:1.55rem}
.hdr .sbar{position:absolute;left:0;right:0;top:100%;z-index:2;background:var(--hdr-bg);color:var(--hdr-ink);border-top:1px solid color-mix(in srgb,var(--hdr-ink) 16%,transparent);box-shadow:0 22px 34px -18px rgba(0,0,0,.5)}
.sbar[hidden]{display:none}
.sbar-in{display:flex;align-items:stretch;gap:.5rem;padding-top:1rem;padding-bottom:1rem}
.sbar button{flex:none}
@media (max-width:30rem){.sbar input{font-size:1rem;padding-left:.8rem}.sbar button{padding:0 1rem}}
.sbar-page{padding:0;margin-top:1rem;max-width:40rem}
.sbar input,.sbar-page input{flex:1;min-width:0;min-height:3.2rem;padding:.6rem 1rem;border:1.5px solid color-mix(in srgb,var(--text) 30%,transparent);border-radius:var(--radius);background:var(--bg);color:var(--text);font:inherit;font-size:1.05rem}
.sbar input:focus,.sbar-page input:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 4px color-mix(in srgb,var(--brand) 18%,transparent)}
.sbar button,.sbar-page button{min-height:3.2rem;padding:0 1.4rem;border:0;border-radius:var(--radius);background:var(--brand);color:var(--brand-ink);font:700 .95rem/1 var(--body-font);cursor:pointer}
.results{list-style:none;margin:0;padding:0;display:grid;gap:0}
.result{padding:1.25rem 0;border-top:1px solid var(--line)}
.result:last-child{border-bottom:1px solid var(--line)}
.result h2,.result h3{font-size:1.4rem;margin:.2rem 0 .35rem}
.result h2 a,.result h3 a{color:inherit;text-decoration:none}
.result h2 a:hover,.result h3 a:hover{text-decoration:underline}
.result p{margin:0;color:var(--muted)}
.result mark{background:color-mix(in srgb,var(--brand2) 55%,transparent);color:inherit;padding:0 .1em;border-radius:2px}
.result-pic{display:grid;gap:1rem}
.result-pic img{width:100%;aspect-ratio:16/10;object-fit:cover;border-radius:var(--radius)}
@media (min-width:40rem){.result-pic{grid-template-columns:11rem 1fr;align-items:start}}

/* sections */
.sec{padding:clamp(3.25rem,8vw,6.25rem) 0}
.sec--soft{background:var(--soft)}
.sec-head{max-width:46rem;margin-bottom:2rem}
.sec-head p{color:var(--muted);font-size:1.15rem}
.eyebrow{display:inline-flex;align-items:center;gap:.6rem;margin:0 0 .9rem;font:700 .8rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--link)}
.eyebrow::before{content:"";width:1.6rem;height:3px;border-radius:3px;background:var(--brand2)}
.center .eyebrow{justify-content:center}
.center{text-align:center;margin-left:auto;margin-right:auto}
.prose p{max-width:44rem}
.center .prose p,.center.prose p{margin-left:auto;margin-right:auto}

/* hero */
.hero{position:relative;color:#fff;overflow:hidden;isolation:isolate;display:flex;align-items:center;min-height:min(86svh,44rem);background:
    radial-gradient(46rem 28rem at 88% -8%,color-mix(in srgb,var(--brand2) 42%,transparent),transparent 62%),
    radial-gradient(38rem 26rem at -8% 112%,color-mix(in srgb,#fff 16%,transparent),transparent 60%),
    linear-gradient(135deg,color-mix(in srgb,var(--brand) 88%,#fff),var(--brand) 38%,color-mix(in srgb,var(--band) 55%,#000))}
.hero--photo::before{content:"";position:absolute;inset:0;background:linear-gradient(100deg,rgba(0,0,0,.72) 0%,rgba(0,0,0,.45) 55%,rgba(0,0,0,.2) 100%);z-index:-1}
.hero-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
.hero--plain::before{content:"";position:absolute;inset:0;z-index:-1;opacity:.5;background-image:radial-gradient(rgba(255,255,255,.22) 1.2px,transparent 1.4px);background-size:26px 26px;-webkit-mask-image:linear-gradient(100deg,transparent 0%,#000 70%);mask-image:linear-gradient(100deg,transparent 0%,#000 70%)}
.hero--plain::after{content:"";position:absolute;right:-9rem;bottom:-11rem;width:34rem;height:34rem;border-radius:50%;border:2.75rem solid color-mix(in srgb,var(--brand2) 38%,transparent);z-index:-1}
.hero .wrap{padding-top:clamp(3.5rem,9vw,6.5rem);padding-bottom:clamp(6rem,14vw,9rem)}
.hero-tag{display:inline-flex;align-items:center;gap:.6rem;margin:0 0 1.4rem;padding:.45rem 1rem .45rem .85rem;border-radius:999px;border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.12);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);font:700 .78rem/1 var(--body-font);letter-spacing:.14em;text-transform:uppercase}
.hero-tag i{width:.55rem;height:.55rem;border-radius:50%;background:var(--brand2);box-shadow:0 0 0 .3rem color-mix(in srgb,var(--brand2) 30%,transparent)}
.hero h1,.hero h2{color:#fff;max-width:17ch;font-size:clamp(2.4rem,7.4vw,4.4rem);letter-spacing:-.02em;text-shadow:0 2px 24px rgba(0,0,0,.18)}
.hero-sub{font-size:clamp(1.1rem,2.6vw,1.4rem);max-width:37rem;opacity:.95;margin-bottom:2rem}
.hero .btn--solid{background:var(--brand2);color:var(--brand2-ink);box-shadow:0 10px 26px -10px rgba(0,0,0,.55)}
.hero .btn--ghost{color:#fff;border-color:rgba(255,255,255,.7)}
.hero .btn--ghost:hover{background:rgba(255,255,255,.12)}

/* quick action cards that overlap the bottom of the banner */
.actions{position:relative;z-index:3;margin-top:calc(-1 * clamp(3.5rem,8vw,5rem))}
.actions-grid{display:grid;gap:1rem;grid-template-columns:1fr}
.action{display:flex;align-items:center;gap:1rem;padding:1.15rem 1.25rem;background:var(--bg);color:var(--text);border-radius:var(--radius-lg);box-shadow:0 22px 50px -22px rgba(0,0,0,.45),0 0 0 1px var(--line);text-decoration:none;transition:transform .18s,box-shadow .18s}
a.action:hover{transform:translateY(-4px);box-shadow:0 28px 56px -22px rgba(0,0,0,.5),0 0 0 1px var(--brand)}
.action-ico,.card-ico{display:grid;place-items:center;flex:none;width:3.25rem;height:3.25rem;border-radius:1rem;background:color-mix(in srgb,var(--brand) 11%,var(--bg));color:var(--link)}
.action-ico .ico,.card-ico .ico{width:1.55rem;height:1.55rem}
.action-text{flex:1;min-width:0}
.action-text b{display:block;font:700 1.08rem/1.25 var(--body-font)}
.action-text span{display:block;color:var(--muted);font-size:.95rem;line-height:1.4;margin-top:.15rem}
.action-go{flex:none;color:var(--link);transition:transform .18s}
.action-go .ico{width:1.3rem;height:1.3rem}
a.action:hover .action-go{transform:translateX(4px)}
@media (min-width:56rem){.actions-grid{grid-template-columns:repeat(3,1fr);gap:1.25rem}.actions-grid:has(.action:nth-child(4)){grid-template-columns:repeat(4,1fr)}.action{padding:1.4rem 1.5rem}}
.ico{display:block}

/* text + picture */
.split{display:grid;gap:2rem;align-items:center}
.split img{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:var(--radius-lg)}
@media (min-width:52rem){.split{grid-template-columns:1fr 1fr;gap:4rem}.split--left img{order:-1}}

/* cards */
.cards{display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,16.5rem),1fr))}
.card{background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-lg);overflow:hidden;display:flex;flex-direction:column;transition:transform .2s,box-shadow .2s}
.card:hover{transform:translateY(-4px);box-shadow:0 24px 48px -26px rgba(0,0,0,.4)}
.card .card-ico{margin-bottom:1.1rem}
.sec--soft .card{border-color:transparent}
.card img{width:100%;aspect-ratio:3/2;object-fit:cover}
.card-body{padding:1.4rem 1.5rem 1.6rem}
.card h3{margin-bottom:.4rem}
.card p{margin:0;color:var(--muted)}
.card--plain .card-body{padding:1.75rem}
.card--plain::before{content:"";display:block;height:.35rem;background:var(--brand2)}

/* numbers */
.stats{display:grid;grid-template-columns:repeat(2,1fr);gap:0;text-align:center}
.stat{padding:1.5rem .5rem;border-left:1px solid var(--line)}
.stat:nth-child(odd){border-left:0}
.stat b{display:block;font-family:var(--head-font);font-weight:400;font-size:clamp(2.4rem,8vw,3.8rem);line-height:1;color:var(--link)}
.stat span{display:block;margin-top:.4rem;color:var(--muted)}
@media (min-width:52rem){.stats{grid-template-columns:repeat(auto-fit,minmax(0,1fr))}.stat:nth-child(odd){border-left:1px solid var(--line)}.stat:first-child{border-left:0}}

/* news */
.news{display:grid;gap:1.25rem}
.news-item{display:flex;flex-direction:column;border-radius:var(--radius-lg);background:var(--bg);border:1px solid var(--line);overflow:hidden}
.sec--soft .news-item{border-color:transparent}
.news-item img{width:100%;aspect-ratio:16/10;object-fit:cover}
.news-body{display:flex;flex-direction:column;gap:.35rem;padding:1.4rem 1.5rem 1.5rem}
.news-item h3{margin:.2rem 0;font-size:1.3rem}
.news-item h3 a{color:inherit;text-decoration:none}
.news-item h3 a:hover{text-decoration:underline}
.news-item p{margin:0;color:var(--muted)}
.tag{font:700 .8rem/1 var(--body-font);letter-spacing:.12em;text-transform:uppercase;color:var(--link)}
.date{font:600 .85rem/1 var(--body-font);letter-spacing:.08em;text-transform:uppercase;color:var(--link);margin-top:.5rem}
@media (min-width:44rem){.news--grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (min-width:64rem){
  .news--grid{grid-template-columns:repeat(3,minmax(0,1fr))}
  .news--feature{grid-template-columns:1.4fr 1fr;grid-auto-rows:auto}
  .news--feature .news-item:first-child{grid-row:span 3}
  .news--feature .news-item:first-child h3{font-size:1.9rem}
  .news--feature .news-item:not(:first-child).news-item--pic{flex-direction:row}
  .news--feature .news-item:not(:first-child).news-item--pic img{width:38%;aspect-ratio:auto}
}
.sec-head--row{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:.5rem 1.5rem;max-width:none}
.more{font-weight:700;text-decoration:none;white-space:nowrap}
.sec--tight{padding-top:1rem}
.page-head{padding-bottom:1rem}
.chips{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1rem}
.chips a{display:inline-flex;align-items:center;min-height:2.5rem;padding:.35rem 1rem;border-radius:999px;border:1px solid var(--line);color:inherit;text-decoration:none;font-weight:600;font-size:.95rem}
.chips a[aria-current]{background:var(--brand);border-color:var(--brand);color:var(--brand-ink)}
.pager{display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:2.5rem;flex-wrap:wrap}
.narrow{max-width:46rem}
.crumb{display:inline-block;margin-bottom:1rem;font-weight:600;text-decoration:none}
.post-head .tag{display:block;margin-bottom:.75rem}
.lede{font-size:1.25rem;color:var(--muted)}
.cover{width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:var(--radius-lg)}
.event-box{display:flex;flex-wrap:wrap;gap:1rem 2.5rem;margin:1.25rem 0 0;padding:1rem 1.25rem;border-left:4px solid var(--brand2);background:var(--soft);border-radius:var(--radius)}
.event-box dt{font:700 .75rem/1 var(--body-font);letter-spacing:.12em;text-transform:uppercase;color:var(--link);margin-bottom:.3rem}
.event-box dd{margin:0;font-weight:600}
.prose h2{font-size:clamp(1.5rem,4vw,2rem);margin-top:1.6em}
.prose h3{margin-top:1.4em}
.prose ul,.prose ol{padding-left:1.4rem;margin:0 0 1em;max-width:44rem}
.prose li{margin:.4em 0}
.prose ul{list-style:none;padding-left:0}
.prose ul li{position:relative;padding-left:1.9rem}
.prose ul li::before{content:"";position:absolute;left:0;top:.5em;width:1.05rem;height:1.05rem;border-radius:50%;background:color-mix(in srgb,var(--brand) 14%,var(--bg)) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m5 12 5 5 9-10'/%3E%3C/svg%3E") center/70% no-repeat}
.prose blockquote{margin:1.5em 0;padding:.25em 0 .25em 1.25em;border-left:4px solid var(--brand2);font-family:var(--head-font);font-size:1.3rem}
.share{display:flex;align-items:center;gap:.6rem;margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid var(--line);font-weight:700}
.share a{display:grid;place-items:center;width:2.75rem;height:2.75rem;border-radius:50%;background:var(--soft);color:var(--text)}
.share svg{width:1.2rem;height:1.2rem;fill:currentColor}
.ftr-tag{margin:.75rem 0 0;opacity:.85}

/* call to action */
.cta{background:var(--brand);color:var(--brand-ink);text-align:center;padding:clamp(3rem,8vw,5.5rem) 0}
.cta h1,.cta h2{color:inherit}
.cta p{max-width:36rem;margin:0 auto 1.75rem;opacity:.92;font-size:1.15rem}
.cta .btns{justify-content:center;max-width:26rem;margin:0 auto}
.cta .btn{flex:1 1 14rem;color:inherit;border-color:currentColor}
.cta .btn--solid{background:var(--brand2);color:var(--brand2-ink);border-color:var(--brand2)}

/* faq */
.faq{max-width:46rem}
.faq details{border-bottom:1px solid var(--line)}
.faq details[open] summary{color:var(--link)}
.faq summary{cursor:pointer;list-style:none;padding:1.15rem 2.5rem 1.15rem 0;font:600 1.1rem/1.4 var(--body-font);position:relative}
.faq summary::-webkit-details-marker{display:none}
.faq summary::after{content:"+";position:absolute;right:.25rem;top:1rem;font-size:1.6rem;line-height:1;color:var(--link);transition:transform .2s}
.faq details[open] summary::after{transform:rotate(45deg)}
.faq details p{padding-bottom:1.2rem;color:var(--muted);margin:0}

/* contact */
.contact{display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,15rem),1fr))}
.contact .card-body b{display:block;font:700 .8rem/1 var(--body-font);letter-spacing:.12em;text-transform:uppercase;color:var(--link);margin-bottom:.5rem}
.contact .card-body{font-size:1.15rem}

/* footer */
.ftr{background:var(--ftr-bg);color:var(--ftr-ink);padding:3rem 0 2rem;font-size:1rem}
.ftr a{color:inherit}
.ftr-grid{display:grid;gap:2rem}
.ftr h2{font:400 1.4rem/1.2 var(--head-font);margin-bottom:.75rem;color:inherit}
.ftr ul{list-style:none;margin:0;padding:0}
.ftr li{margin:.15rem 0}
.ftr li a{display:inline-block;padding:.35rem 0;font-weight:600;text-decoration:none}
.ftr li a:hover{text-decoration:underline}
.ftr-brand{display:flex;align-items:center;gap:.8rem;font:400 1.6rem/1.1 var(--head-font)}
.ftr-brand img{height:3.2rem;width:auto}
.social{display:flex;gap:.9rem;margin:1.25rem 0 0;padding:0;list-style:none}
.social a{display:grid;place-items:center;width:2.75rem;height:2.75rem;border-radius:50%;background:rgba(127,127,127,.18);color:inherit}
.social a:hover{background:rgba(127,127,127,.32)}
.social svg{width:1.3rem;height:1.3rem;fill:currentColor}
.legal{margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid rgba(127,127,127,.3);font-size:.9rem;display:flex;flex-direction:column;gap:.35rem;align-items:center;text-align:center}
.legal p{margin:0;opacity:.88}
.powered a{font-weight:700}
@media (min-width:52rem){.legal{flex-direction:row;justify-content:space-between;text-align:left}}
@media (min-width:52rem){.ftr-grid{grid-template-columns:1.4fr 1fr 1fr}}


/* steps */
.steps{list-style:none;margin:0;padding:0;display:grid;gap:1.25rem;counter-reset:s}
.steps li{position:relative;padding:1.6rem 1.5rem 1.5rem;border-radius:var(--radius-lg);background:var(--soft);border:1px solid transparent}
.sec--soft .steps li{background:var(--bg)}
.step-n{display:grid;place-items:center;width:2.6rem;height:2.6rem;margin-bottom:1rem;border-radius:50%;background:var(--brand);color:var(--brand-ink);font:700 1.1rem/1 var(--body-font);box-shadow:0 0 0 .4rem color-mix(in srgb,var(--brand) 14%,transparent)}
.steps h3{font-size:1.2rem;margin-bottom:.35rem}
.steps p{margin:0;color:var(--muted)}
@media (min-width:44rem){.steps{grid-template-columns:repeat(2,1fr)}}
@media (min-width:64rem){.steps{grid-template-columns:repeat(auto-fit,minmax(0,1fr))}.steps li:not(:last-child)::after{content:"";position:absolute;top:2.9rem;left:calc(100% - .2rem);width:1.4rem;height:2px;background:color-mix(in srgb,var(--brand) 35%,transparent)}}

/* top strip */
.top{display:none;background:color-mix(in srgb,var(--brand) 86%,#000);color:var(--brand-ink);font-size:.86rem}
.top a{color:inherit;text-decoration:none;opacity:.92}
.top a:hover{opacity:1;text-decoration:underline}
.top-in{display:flex;justify-content:space-between;align-items:center;gap:1rem;min-height:2.4rem}
.top-info,.top-links{display:flex;gap:1.5rem;flex-wrap:wrap}
@media (min-width:60rem){.top{display:block}}

/* pages that are not built from sections (apply, sign in) */
.pagehero{position:relative;color:#fff;overflow:hidden;isolation:isolate;padding:clamp(2.6rem,7vw,4.5rem) 0 clamp(4.5rem,11vw,6.5rem);background:
    radial-gradient(34rem 20rem at 92% -10%,color-mix(in srgb,var(--brand2) 40%,transparent),transparent 62%),
    linear-gradient(135deg,color-mix(in srgb,var(--brand) 88%,#fff),var(--brand) 40%,color-mix(in srgb,var(--band) 55%,#000))}
.pagehero::before{content:"";position:absolute;inset:0;z-index:-1;opacity:.45;background-image:radial-gradient(rgba(255,255,255,.22) 1.2px,transparent 1.4px);background-size:26px 26px;-webkit-mask-image:linear-gradient(100deg,transparent 0%,#000 75%);mask-image:linear-gradient(100deg,transparent 0%,#000 75%)}
.pagehero h1{color:#fff;font-size:clamp(2rem,6vw,3.2rem);margin-bottom:.4em;max-width:20ch}
.pagehero .hero-tag{font-size:.78rem;opacity:1;margin:0 0 1.1rem}
.pagehero p{max-width:38rem;font-size:1.15rem;opacity:.95;margin:0}
.formwrap{position:relative;z-index:2;margin-top:calc(-1 * clamp(2.5rem,6vw,3.5rem));padding-bottom:clamp(3rem,8vw,5rem)}
.formgrid{display:grid;gap:1.5rem;align-items:start}
@media (min-width:60rem){.formgrid{grid-template-columns:minmax(0,1.5fr) minmax(0,1fr)}.formgrid--solo{grid-template-columns:minmax(0,40rem)}}
.panel{background:var(--bg);border-radius:var(--radius-lg);padding:clamp(1.4rem,4vw,2.25rem);box-shadow:0 24px 56px -28px rgba(0,0,0,.45),0 0 0 1px var(--line)}
.panel h2{font-size:1.5rem;margin-bottom:.35em}
.panel .lede{font-size:1rem;margin-bottom:1.25rem}
.panel--soft{background:var(--soft);box-shadow:none}
.field{display:block;margin:0 0 1.1rem}
.field>span{display:block;margin-bottom:.4rem;font:700 .92rem/1.3 var(--body-font)}
.field>span em{color:var(--link);font-style:normal}
.field input,.field select,.field textarea{display:block;width:100%;min-height:3.1rem;padding:.7rem 1rem;border:1.5px solid color-mix(in srgb,var(--text) 22%,transparent);border-radius:var(--radius);background:var(--bg);color:var(--text);font:inherit;font-size:1rem;transition:border-color .15s,box-shadow .15s}
.field textarea{min-height:7rem;resize:vertical}
.field input:focus,.field select:focus,.field textarea:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 4px color-mix(in srgb,var(--brand) 18%,transparent)}
.field .err{display:block;margin-top:.35rem;color:#b91c1c;font-size:.9rem;font-weight:600}
.field .hint{display:block;margin-top:.35rem;color:var(--muted);font-size:.88rem}
.two{display:grid;gap:0 1rem}
@media (min-width:40rem){.two{grid-template-columns:1fr 1fr}}
.alert{margin:0 0 1.25rem;padding:.9rem 1.1rem;border-radius:var(--radius);font-weight:600;border:1px solid}
.alert--ok{background:#ecfdf5;border-color:#6ee7b7;color:#065f46}
.alert--bad{background:#fef2f2;border-color:#fca5a5;color:#991b1b}
.btn--block{width:100%}
.check{display:flex;align-items:center;gap:.6rem;margin:0 0 1.25rem;font-size:.95rem}
.check input{width:1.2rem;height:1.2rem;accent-color:var(--brand)}
.hp{position:absolute!important;left:-9999px;width:1px;height:1px;overflow:hidden}
.roles{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin:0 0 1.5rem;padding:.35rem;border-radius:999px;background:var(--soft)}
.roles label{position:relative;display:block;text-align:center;cursor:pointer}
.roles input{position:absolute;opacity:0;inset:0}
.roles span{display:block;padding:.65rem .5rem;border-radius:999px;font:700 .92rem/1.2 var(--body-font);color:var(--muted);transition:background .15s,color .15s}
.roles input:checked+span{background:var(--brand);color:var(--brand-ink);box-shadow:0 8px 18px -10px var(--brand)}
.roles input:focus-visible+span{outline:3px solid var(--brand);outline-offset:2px}
.role-note{margin:0 0 1.25rem;color:var(--muted);font-size:.98rem}
.perks{list-style:none;margin:0;padding:0;display:grid;gap:1rem}
.perks li{display:flex;gap:.9rem;align-items:flex-start}
.perks .card-ico{width:2.75rem;height:2.75rem;border-radius:.85rem}
.perks b{display:block;font:700 1.02rem/1.3 var(--body-font)}
.perks span.t{display:block;color:var(--muted);font-size:.95rem}
.sidelink{margin:1.25rem 0 0;font-size:.95rem}
.thanks{text-align:center;padding:1rem 0}
.thanks .card-ico{margin:0 auto 1rem;width:4rem;height:4rem;border-radius:50%}
.thanks .card-ico .ico{width:2rem;height:2rem}

/* theme touches (Heritage is the base look; the others are in styles-themes) */
[data-theme=heritage] .btn--ghost{letter-spacing:.16em}
[data-theme=heritage] .card{border-radius:var(--radius)}
[data-theme=heritage] .card:has(img){border-radius:var(--radius-lg)}

/* gallery */
.photos{columns:2;column-gap:.75rem}
.photo{position:relative;display:block;margin:0 0 .75rem;break-inside:avoid;overflow:hidden;border-radius:var(--radius-lg);background:var(--soft)}
.photo img{width:100%;height:auto;transition:transform .3s}
.photo:hover img{transform:scale(1.04)}
.photo-cap{position:absolute;left:0;right:0;bottom:0;padding:1.5rem .85rem .65rem;color:#fff;font-size:.9rem;line-height:1.3;background:linear-gradient(transparent,rgba(0,0,0,.7));opacity:0;transition:opacity .2s}
.photo:hover .photo-cap,.photo:focus-visible .photo-cap{opacity:1}
@media (min-width:44rem){.photos{columns:3;column-gap:1rem}.photo{margin-bottom:1rem}}
@media (min-width:64rem){.photos{columns:4}}
.albums{display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,17rem),1fr))}
.album{position:relative;display:block;aspect-ratio:4/3;overflow:hidden;border-radius:var(--radius-lg);background:var(--band);color:#fff;text-decoration:none}
.album img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;transition:transform .4s}
.album:hover img{transform:scale(1.05)}
.album-body{position:absolute;left:0;right:0;bottom:0;display:flex;flex-direction:column;gap:.15rem;padding:2.5rem 1.1rem 1rem;background:linear-gradient(transparent,rgba(0,0,0,.78))}
.album-body b{font:700 1.2rem/1.25 var(--head-font)}
.album-body span{font-size:.9rem;opacity:.9}
.lb{position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.92);padding:1rem}
.lb[hidden]{display:none}
.lb figure{margin:0;max-width:min(94vw,70rem);text-align:center}
.lb img{max-width:100%;max-height:80svh;margin:0 auto;border-radius:.5rem}
.lb figcaption{margin-top:.75rem;color:#fff;font-size:1rem}
.lb button{position:absolute;display:grid;place-items:center;width:3rem;height:3rem;border:0;border-radius:50%;background:rgba(255,255,255,.14);color:#fff;font-size:2rem;line-height:1;cursor:pointer}
.lb button:hover{background:rgba(255,255,255,.28)}
.lb-x{top:1rem;right:1rem}
.lb-prev{left:.5rem;top:50%;transform:translateY(-50%)}
.lb-next{right:.5rem;top:50%;transform:translateY(-50%)}

/* announcement bar, deep bands, quotes and person profiles (every theme; Statement dresses them further in styles-themes) */
.notice{background:var(--brand2);color:var(--brand2-ink)}
.notice .wrap{padding-top:.55rem;padding-bottom:.55rem}
.notice p{margin:0;font-weight:600;font-size:.95rem}
@media (min-width:60rem){.notice p{text-align:center}}
.sec--dark{background:var(--deep);color:var(--deep-ink)}
.sec--dark h1,.sec--dark h2,.sec--dark h3{color:inherit}
.sec--dark .eyebrow{color:var(--brand2-d)}
.sec--dark .sec-head p{color:inherit;opacity:.88}
.sec--quote{padding-top:0}
.sec--tint{background:var(--soft)}

@media (prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto!important}}
@media print{.hdr,.menu,.cta{display:none}}

/* testimonials: one card at a time that visitors swipe through; arrows and dots appear when scripts run */
.tsl-track{display:flex;gap:1.25rem;margin:0;padding:0 0 .5rem;list-style:none;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;-webkit-overflow-scrolling:touch}
.tsl-track::-webkit-scrollbar{display:none}
.tsl-item{flex:0 0 100%;min-width:0;scroll-snap-align:start}
.tcard{margin:0;height:100%;padding:clamp(1.75rem,5vw,2.75rem);background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-lg);display:flex;flex-direction:column;gap:1.5rem}
.sec--soft .tcard{border-color:transparent}
.tcard blockquote{margin:0;font:italic 400 clamp(1.2rem,3.4vw,1.55rem)/1.5 var(--head-font)}
.tcard blockquote p{margin:0}
.tcard figcaption{display:flex;align-items:center;gap:.9rem;margin-top:auto}
.tcard figcaption img{width:3.25rem;height:3.25rem;border-radius:50%;object-fit:cover;flex:none}
.tcard-who{display:flex;flex-direction:column;gap:.15rem;line-height:1.3}
.tcard-who b{font-weight:700}
.tcard-who span{color:var(--muted);font-size:.95rem}
.sec--dark .tcard{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);color:inherit}
.sec--dark .tcard-who span{color:inherit;opacity:.8}
.tsl-nav{display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:1.25rem}
.tsl-btn{display:grid;place-items:center;width:2.9rem;height:2.9rem;border-radius:50%;border:1px solid currentColor;background:transparent;color:var(--link);cursor:pointer}
.sec--dark .tsl-btn{color:#fff}
.tsl-btn svg{width:1.2rem;height:1.2rem}
.tsl-dots{display:flex;gap:.5rem}
.tsl-dots i{width:.55rem;height:.55rem;border-radius:50%;background:currentColor;opacity:.25;transition:opacity .2s,transform .2s}
.tsl-dots i.on{opacity:1;transform:scale(1.25)}
@media (min-width:60rem){.tsl-item{flex-basis:calc((100% - 1.25rem)/2)}}

/* sub-menus: the pages that sit under a menu item, shown indented under it in the full-screen menus */
.menu-sub{list-style:none;margin:0 0 .6rem;padding:0 0 0 1rem;border-left:2px solid color-mix(in srgb,currentColor 30%,transparent)}
body[data-theme] .menu .menu-sub li{margin:0;padding:0;border:0}
body[data-theme] .menu .menu-sub a{display:block;padding:.55rem .75rem;border:0;border-radius:0;background:none;font:500 1rem/1.3 var(--body-font);letter-spacing:0;text-transform:none;text-decoration:none;opacity:.92}
body[data-theme] .menu .menu-sub a:hover,body[data-theme] .menu .menu-sub a[aria-current]{opacity:1;text-decoration:underline;text-underline-offset:.25em;background:none}
@media (min-width:72rem){
  /* on wide screens where the menu sits in the bar, the sub-menu drops down when the item is hovered or tabbed to */
  [data-theme=heritage] .menu-item,[data-theme=modern] .menu-item,[data-theme=statement] .menu-item{position:relative;display:flex;align-items:center}
  [data-theme=heritage] .menu .menu-sub,[data-theme=modern] .menu .menu-sub,[data-theme=statement] .menu .menu-sub{display:none;position:absolute;top:100%;left:0;z-index:80;min-width:14rem;margin:0;padding:.5rem 0;border:0;background:#fff;color:#222;box-shadow:0 18px 40px -16px rgba(0,0,0,.45)}
  [data-theme=heritage] .menu-item:hover>.menu-sub,[data-theme=heritage] .menu-item:focus-within>.menu-sub,[data-theme=modern] .menu-item:hover>.menu-sub,[data-theme=modern] .menu-item:focus-within>.menu-sub,[data-theme=statement] .menu-item:hover>.menu-sub,[data-theme=statement] .menu-item:focus-within>.menu-sub{display:block}
  body[data-theme=heritage] .menu .menu-sub a,body[data-theme=modern] .menu .menu-sub a,body[data-theme=statement] .menu .menu-sub a{padding:.7rem 1.2rem;color:#222;font-size:.95rem;opacity:1;white-space:nowrap;text-transform:none;letter-spacing:0}
  body[data-theme=heritage] .menu .menu-sub a:hover,body[data-theme=modern] .menu .menu-sub a:hover,body[data-theme=statement] .menu .menu-sub a:hover{background:#f1f1f1;text-decoration:none}
}
em[data-rotate]{display:inline-block;transition:opacity .35s ease}
em[data-rotate].rot-out{opacity:0}
@media (min-width:72rem){body[data-theme=statement] .menu .menu-sub{margin-left:0}}
@endverbatim

/* Logo fitting: wide logos get a wider box; a logo that would be hard to see sits on a soft badge (see App\Website\LogoFit) */
[data-theme] .brand img.logo--wide{max-width:min(9.5rem,36vw)}
[data-theme] .ftr-brand img.logo--wide,[data-theme] .fh-brand img.logo--wide{max-width:min(14rem,70vw);height:auto;max-height:4rem}
[data-theme] .mf-logo img.logo--wide{max-width:min(18rem,80vw)}
[data-theme] img.logo--boxed{border-radius:.4rem}
[data-theme] img.logo--badge{box-sizing:content-box;padding:.3rem;border-radius:.6rem;box-shadow:0 0 0 1px rgba(0,0,0,.08),0 2px 8px rgba(0,0,0,.12);object-fit:contain}
[data-theme] img.logo--badge-white{background:#fff}
[data-theme] img.logo--badge-cream{background:#fbf6ea}
[data-theme] img.logo--badge-dark{background:var(--deep);box-shadow:0 0 0 1px rgba(255,255,255,.18),0 2px 8px rgba(0,0,0,.2)}
[data-theme] img.logo--badge.logo--rounded{border-radius:.6rem}
[data-theme] img.logo--badge.logo--square{border-radius:0}
[data-theme] img.logo--badge.logo--circle{border-radius:50%;padding:.45rem}
[data-theme=modern] .brand img.logo--badge{border-right:0;margin-right:.85rem;padding:.3rem}
