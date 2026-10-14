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
.hdr{position:sticky;top:0;z-index:50;background:var(--hdr-bg);color:var(--hdr-ink);border-bottom:1px solid var(--line)}
.hdr-in{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:4.25rem}
.brand{display:flex;align-items:center;gap:.7rem;color:var(--hdr-name);text-decoration:none;min-width:0}
.brand img{height:2.6rem;width:auto;max-width:3.4rem;object-fit:contain}
.brand span{font:400 1.3rem/1.1 var(--head-font);letter-spacing:-.01em;overflow:hidden;text-overflow:ellipsis}
.burger{display:inline-flex;flex-direction:column;justify-content:center;gap:.3rem;width:3rem;height:3rem;padding:.8rem;background:none;border:0;color:var(--hdr-ink);cursor:pointer}
.burger i{display:block;height:2px;background:currentColor;border-radius:2px;transition:transform .2s,opacity .2s}
.menu-open .burger i:nth-child(1){transform:translateY(.4rem) rotate(45deg)}
.menu-open .burger i:nth-child(2){opacity:0}
.menu-open .burger i:nth-child(3){transform:translateY(-.4rem) rotate(-45deg)}
.menu{display:none;position:fixed;inset:4.25rem 0 0 0;background:var(--brand);color:var(--brand-ink);padding:1.5rem 1.5rem 3rem;overflow:auto;z-index:40}
.menu-open .menu{display:block}
.menu a{display:block;color:inherit;text-decoration:none;font:400 1.9rem/1.2 var(--head-font);padding:.85rem 0;border-bottom:1px solid rgba(255,255,255,.18)}
.menu a[aria-current]{text-decoration:underline;text-underline-offset:.3em}
.menu .btn{margin-top:1.5rem;width:100%;font-size:1rem;border-color:currentColor;color:inherit}
body.menu-open{overflow:hidden}
@media (min-width:60rem){
  .burger{display:none}
  .menu,.menu-open .menu{display:flex;align-items:center;gap:.25rem;position:static;background:none;color:inherit;padding:0;overflow:visible}
  .menu a{font:600 .95rem/1 var(--body-font);padding:.7rem .85rem;border:0;border-radius:999px;opacity:.9}
  .menu a:hover{opacity:1;background:rgba(0,0,0,.06)}
  .menu a[aria-current]{opacity:1;background:rgba(0,0,0,.08);text-decoration:none}
  .menu .btn{margin:0 0 0 .5rem;width:auto;min-height:2.5rem;padding:.5rem 1.2rem;font-size:.85rem;background:var(--brand);border-color:var(--brand);color:var(--brand-ink)}
}

/* sections */
.sec{padding:clamp(2.75rem,7vw,5.5rem) 0}
.sec--soft{background:var(--soft)}
.sec-head{max-width:46rem;margin-bottom:2rem}
.sec-head p{color:var(--muted);font-size:1.15rem}
.center{text-align:center;margin-left:auto;margin-right:auto}
.prose p{max-width:44rem}
.center .prose p,.center.prose p{margin-left:auto;margin-right:auto}

/* hero */
.hero{position:relative;color:#fff;overflow:hidden;isolation:isolate;display:flex;align-items:flex-end;min-height:min(78svh,40rem);background:linear-gradient(135deg,var(--brand),color-mix(in srgb,var(--brand) 55%,#000))}
.hero--photo::before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.15) 0%,rgba(0,0,0,.65) 100%);z-index:-1}
.hero-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
.hero .wrap{padding-top:5rem;padding-bottom:clamp(2.5rem,7vw,5rem)}
.hero h1,.hero h2{color:#fff;max-width:16ch}
.hero p{font-size:clamp(1.1rem,2.6vw,1.4rem);max-width:36rem;opacity:.95}
.hero .btn--solid{background:var(--brand2);color:var(--brand2-ink)}
.hero .btn--ghost{color:#fff}
.hero:not(.hero--photo)::after{content:"";position:absolute;right:-8rem;top:-8rem;width:26rem;height:26rem;border-radius:50%;background:var(--brand2);opacity:.18;z-index:-1}

/* text + picture */
.split{display:grid;gap:2rem;align-items:center}
.split img{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:var(--radius-lg)}
@media (min-width:52rem){.split{grid-template-columns:1fr 1fr;gap:4rem}.split--left img{order:-1}}

/* cards */
.cards{display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,16.5rem),1fr))}
.card{background:var(--bg);border:1px solid var(--line);border-radius:var(--radius-lg);overflow:hidden;display:flex;flex-direction:column}
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
.prose li{margin:.3em 0}
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
.legal{margin-top:2.5rem;padding-top:1.5rem;border-top:1px solid rgba(127,127,127,.3);font-size:.9rem;opacity:.85;text-align:center}
@media (min-width:52rem){.ftr-grid{grid-template-columns:1.4fr 1fr 1fr}}

/* theme touches */
[data-theme=heritage] .btn--ghost{letter-spacing:.16em}
[data-theme=heritage] .card{border-radius:var(--radius)}
[data-theme=heritage] .card:has(img){border-radius:var(--radius-lg)}
[data-theme=modern] .hero{min-height:min(72svh,36rem)}
[data-theme=modern] .card,[data-theme=modern] .news-item{box-shadow:0 10px 30px -18px rgba(15,23,42,.35)}
[data-theme=modern] .sec--soft .card{box-shadow:0 10px 30px -18px rgba(15,23,42,.25)}
[data-theme=modern] h1,[data-theme=modern] h2,[data-theme=modern] h3{font-weight:800;letter-spacing:-.03em}
[data-theme=warm] .hero{border-radius:0 0 var(--radius-lg) var(--radius-lg)}
[data-theme=warm] .cta{border-radius:var(--radius-lg);margin:0 1.25rem}
[data-theme=warm] .card--plain::before{display:none}
[data-theme=warm] .card--plain{background:var(--bg);box-shadow:0 12px 28px -20px rgba(120,53,15,.5)}

@media (prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto!important}}
@media print{.hdr,.menu,.cta{display:none}}
@endverbatim
