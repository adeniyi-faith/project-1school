@verbatim
/* ============ MODERN: solid navy bar, full-screen photo banner, square red buttons, slanted panel edges, blue footer ============ */
[data-theme=modern]{--hdr-h:4.4rem;--cut:4.2rem;--rule:#cfd4dc}
[data-theme=modern] h1,[data-theme=modern] h2,[data-theme=modern] h3{font-family:var(--head-font);font-weight:800;color:var(--brand-t);text-transform:uppercase;letter-spacing:-.01em;line-height:1.08}
[data-theme=modern] h1{font-size:clamp(2.3rem,9vw,4.6rem)}
[data-theme=modern] h2{font-size:clamp(2rem,7.5vw,3.4rem)}
[data-theme=modern] h3{font-size:1.3rem}
[data-theme=modern] .sec--dark h1,[data-theme=modern] .sec--dark h2,[data-theme=modern] .sec--dark h3,[data-theme=modern] .sec--navy h1,[data-theme=modern] .sec--navy h2{color:inherit}
[data-theme=modern] body,[data-theme=modern]{line-height:1.8}
[data-theme=modern] .eyebrow{display:block;margin:0 0 .7rem;font:800 .85rem/1.4 var(--body-font);letter-spacing:.32em;text-transform:uppercase;color:var(--brand-t)}
[data-theme=modern] .eyebrow::before{display:none}
[data-theme=modern] .center .eyebrow{text-align:center}
[data-theme=modern] .sec{padding:clamp(3rem,8vw,5.5rem) 0}
[data-theme=modern] .sec--soft{background:var(--soft)}
[data-theme=modern] .sec--navy{background:var(--ftr-bg);color:var(--ftr-ink)}
[data-theme=modern] .prose p,[data-theme=modern] .sec-head p:not(.eyebrow){font-size:1.08rem;line-height:1.95}
[data-theme=modern] .sec-head{max-width:50rem}
[data-theme=modern] .split img{border-radius:0}
[data-theme=modern] .prose blockquote{border-left:4px solid var(--brand2);font-family:var(--serif-font);font-size:1.5rem}
[data-theme=modern] .prose h2{font-size:clamp(1.5rem,4.5vw,2.1rem)}

/* buttons: square, wide-spaced capitals */
[data-theme=modern] .btn{border-radius:0;border:0;min-height:3.6rem;padding:0 2.4rem;font:500 .84rem/1.2 var(--body-font);letter-spacing:.28em;text-transform:uppercase;transform:none}
[data-theme=modern] .btn--solid{background:var(--brand2);color:var(--brand2-ink);box-shadow:none}
[data-theme=modern] .btn--solid:hover{filter:brightness(.86);box-shadow:none}
[data-theme=modern] .btn--navy{background:var(--brand);color:var(--brand-ink)}
[data-theme=modern] .btn--navy:hover{filter:brightness(1.25)}

/* header: solid bar; see-through over a photo banner until the page is scrolled */
[data-theme=modern] .hdr{--hc:var(--hdr-ink);background:var(--brand);color:var(--hc);border:0;box-shadow:none;transition:background .25s}
[data-theme=modern] .hdr-in{min-height:var(--hdr-h)}
body[data-theme=modern]:has(main>.hero--modern:first-of-type) .hdr{--hc:#fff;background:transparent}
body[data-theme=modern].scrolled:has(main>.hero--modern:first-of-type) .hdr{--hc:var(--hdr-ink);background:var(--brand)}
body[data-theme=modern].menu-open .hdr,body[data-theme=modern].search-open .hdr{background:var(--brand)!important;--hc:var(--hdr-ink)!important}
[data-theme=modern] main>.hero--modern:first-of-type{margin-top:calc(var(--hdr-h) * -1)}
[data-theme=modern] .brand{color:var(--hc);gap:0}
[data-theme=modern] .brand img{box-sizing:content-box;height:2.7rem;width:auto;max-width:3.2rem;padding-right:.85rem;margin-right:.85rem;border-right:1px solid color-mix(in srgb,var(--hc) 55%,transparent)}
[data-theme=modern] .brand span{font:500 clamp(.88rem,3.6vw,1.1rem)/1.15 var(--serif-font);letter-spacing:.05em;text-transform:uppercase;white-space:normal;overflow:visible;max-width:min(15rem,calc(100vw - 12.5rem))}
[data-theme=modern] .sbtn,[data-theme=modern] .burger{color:var(--hc)}
[data-theme=modern] .burger{width:3.4rem;padding:.8rem .55rem;gap:.35rem}
[data-theme=modern] .burger i{height:3px;border-radius:0}
[data-theme=modern] .menu-open .burger i:nth-child(1){transform:translateY(.65rem) rotate(45deg)}
[data-theme=modern] .menu-open .burger i:nth-child(3){transform:translateY(-.65rem) rotate(-45deg)}

/* the menu: a full navy sheet, capitals, a rule and an arrow on every row */
[data-theme=modern] .menu{inset:0;z-index:60;background:var(--brand);color:var(--hdr-ink);padding:0 1.25rem 2.5rem}
[data-theme=modern] .menu-top{display:flex;align-items:center;justify-content:space-between;min-height:var(--hdr-h);margin:0 0 1.25rem}
[data-theme=modern] .menu .brand{display:flex;padding:0;border:0;font:inherit;color:var(--hdr-ink);--hc:var(--hdr-ink)}
[data-theme=modern] .menu-x{width:3.6rem;height:3.6rem;border:1px solid currentColor;background:none;color:inherit;font:400 1.6rem/1 var(--body-font);cursor:pointer}
[data-theme=modern] .menu-list{list-style:none;margin:0;padding:0}
[data-theme=modern] .menu-list li{border-bottom:1px solid color-mix(in srgb,currentColor 85%,transparent)}
[data-theme=modern] .menu .menu-list a{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 0;border:0;font:400 1.15rem/1.25 var(--body-font);letter-spacing:.01em;text-transform:uppercase;color:inherit;text-decoration:none;opacity:1}
[data-theme=modern] .menu .menu-list a[aria-current]{font-weight:800;text-decoration:none}
[data-theme=modern] .menu .menu-list a[aria-current] span::before{content:"";display:inline-block;width:.55rem;height:.55rem;margin-right:.75rem;background:var(--brand2-d)}
[data-theme=modern] .menu-list svg{flex:none;width:1.7rem;height:1.7rem}
[data-theme=modern] .menu .menu-apply{display:flex;margin:2rem 0 0;width:100%;background:var(--brand2);color:var(--brand2-ink)}
@media (min-width:72rem){
  [data-theme=modern] .menu,[data-theme=modern].menu-open .menu{position:static;inset:auto;display:flex;align-items:center;background:none;color:var(--hc);padding:0}
  [data-theme=modern] .menu-top{display:none}
  [data-theme=modern] .menu-list{display:flex;align-items:center;gap:.1rem}
  [data-theme=modern] .menu-list li{border:0}
  [data-theme=modern] .menu-list svg{display:none}
  [data-theme=modern] .menu .menu-list a{padding:.9rem .7rem;font:600 .68rem/1 var(--body-font);letter-spacing:.16em;color:var(--hc);border-bottom:3px solid transparent;border-radius:0}
  [data-theme=modern] .menu .menu-list a:hover{background:none;border-bottom-color:var(--brand2)}
  [data-theme=modern] .menu .menu-list a[aria-current]{background:none;border-bottom-color:var(--brand2);font-weight:700}
  [data-theme=modern] .menu .menu-apply{display:inline-flex;margin:0 0 0 .75rem;width:auto;min-height:2.7rem;padding:0 1.3rem;font-size:.68rem;letter-spacing:.2em}
  [data-theme=modern] .burger{display:none}
  [data-theme=modern] .hdr-tools{order:3}
  [data-theme=modern] .menu{order:2;margin-left:auto}
}
[data-theme=modern] .sbar{border-top-color:rgba(255,255,255,.22)}
[data-theme=modern] .sbar input,[data-theme=modern] .sbar-page input{border-radius:0}
[data-theme=modern] .sbar button{border-radius:0;background:var(--brand2);color:var(--brand2-ink);font:500 .8rem/1 var(--body-font);letter-spacing:.24em;text-transform:uppercase}

/* the banner: a full-screen photo, a kicker, a huge heading, stacked red buttons, a slanted white foot */
[data-theme=modern] .hero--modern{display:flex;align-items:flex-end;min-height:min(100svh,52rem);padding:0;color:#fff;background:var(--band)}
[data-theme=modern] .hero--modern::before{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,12,32,.66) 0,rgba(0,12,32,.48) 40%,rgba(0,12,32,.72) 100%)}
[data-theme=modern] .hero--modern::after{display:none}
[data-theme=modern] .hero-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:-2}
[data-theme=modern] .hero .hero-in{position:relative;padding-top:8rem;padding-bottom:calc(clamp(3.5rem,12vw,7rem) + 2.5rem)}
[data-theme=modern] .hero-kick{margin:0 0 .6rem;font:800 clamp(.78rem,3.1vw,1.15rem)/1.35 var(--body-font);letter-spacing:.3em;text-transform:uppercase}
[data-theme=modern] .hero h1,[data-theme=modern] .hero h2{max-width:13em;margin:0;color:#fff;font:800 clamp(2.35rem,10.5vw,5rem)/1.04 var(--head-font);letter-spacing:-.01em;text-transform:uppercase;text-shadow:none}
[data-theme=modern] .hero h1 em,[data-theme=modern] .hero h2 em{font-style:normal}
[data-theme=modern] .hero-sub{max-width:34rem;margin:1.25rem 0 0;font-size:1.1rem;font-weight:500;line-height:1.6}
[data-theme=modern] .hero .btns{flex-direction:column;align-items:flex-start;gap:.7rem;margin-top:2rem}
[data-theme=modern] .hero .btn--solid{box-shadow:none;background:var(--brand2);color:var(--brand2-ink)}
[data-theme=modern] .hero-down{display:block;margin-top:2.25rem;color:#fff;width:2.4rem}
[data-theme=modern] .hero-down svg{display:block;width:2.4rem;height:4rem}
[data-theme=modern] .hero-cut{position:absolute;left:0;right:0;bottom:-1px;height:clamp(3.5rem,18vw,10rem);background:var(--bg);clip-path:polygon(0 100%,100% 100%,100% 0)}
@media (min-width:40rem){[data-theme=modern] .hero .btns{flex-direction:row;flex-wrap:wrap;gap:1rem}}

/* the red "get started" panel: big title, ruled rows, a navy button, a slanted foot */
[data-theme=modern] .actions{margin-top:calc(-1 * clamp(3.5rem,12vw,7rem));padding-bottom:1rem}
.mo-panel{background:var(--brand2);color:var(--brand2-ink);padding:clamp(2rem,6vw,3rem) clamp(1.75rem,6vw,3.5rem) calc(var(--cut) + 2.25rem);clip-path:polygon(0 0,100% 0,100% 100%,0 calc(100% - var(--cut)))}
[data-theme=modern] .mo-panel h2{margin:0 0 1.75rem;color:inherit;font-size:clamp(2.3rem,9vw,3.4rem);text-transform:none}
.mo-rows{list-style:none;margin:0 0 2.25rem;padding:0}
.mo-rows li{border-bottom:1px solid currentColor}
.mo-rows a,.mo-rows div{display:block;padding:1.1rem 0;color:inherit;text-decoration:none}
.mo-rows b{display:block;font:700 1.15rem/1.35 var(--body-font)}
.mo-rows span{display:block;margin-top:.2rem;font-size:.95rem;line-height:1.55;opacity:.92}
.actions .mo-rows li:first-child b::before{content:"";display:inline-block;width:.5rem;height:.5rem;margin:0 1rem .15rem 0;background:currentColor}
.mo-panel .btn{display:flex;width:100%;text-align:center}
@media (min-width:56rem){
  .actions .mo-panel{display:grid;grid-template-columns:1fr 1.4fr;column-gap:4rem;align-items:start}
  .actions .mo-panel h2{grid-row:1/3}
  .actions .mo-rows{grid-column:2}
  .actions .mo-panel .btn{grid-column:2;width:auto;justify-self:start}
}

/* tiles: a picture over a navy panel with a slanted foot */
.mo-tiles{display:grid;gap:2.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,17.5rem),1fr))}
.mo-tile{display:flex;flex-direction:column;background:var(--brand);color:var(--brand-ink);clip-path:polygon(0 0,100% 0,100% calc(100% - 3.6rem),0 100%)}
.mo-tile img{width:100%;aspect-ratio:3/2;object-fit:cover}
.mo-tile-body{padding:2.25rem 1.75rem 5.4rem;text-align:center}
[data-theme=modern] .mo-tile h3{margin:0 0 .8rem;color:inherit;font-size:1.45rem}
.mo-tile p{margin:0;font-size:1rem;line-height:1.7;opacity:.93}
.mo-tile .mo-tile-body>p.mo-num{margin:0 0 .8rem;font:800 .8rem/1 var(--body-font);letter-spacing:.3em;text-transform:uppercase;color:var(--gold-lt);opacity:1}
.mo-ico{display:inline-grid;place-items:center;width:3.4rem;height:3.4rem;margin-bottom:1rem;border:2px solid currentColor}
.mo-ico .ico{width:1.6rem;height:1.6rem}

/* round seals for the numbers */
[data-theme=modern] .sec--stats{background:var(--bg)}
.mo-seals{display:grid;grid-template-columns:repeat(auto-fit,minmax(9.5rem,1fr));gap:1.5rem;justify-items:center;margin-top:2rem;color:var(--brand-t)}
.mo-seal{margin:0;width:100%;max-width:12.5rem}
.mo-seal svg{display:block;width:100%;height:auto}
.mo-seal-ring{font:700 11px var(--body-font);letter-spacing:.08em;fill:currentColor}
.mo-seal-num{font:800 34px var(--body-font);fill:currentColor}

/* stories */
.mo-stories{display:grid;gap:3rem 2.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,18.5rem),1fr))}
.mo-story img{width:100%;aspect-ratio:1;object-fit:cover;margin-bottom:1.5rem}
[data-theme=modern] .mo-story h3{margin:0 0 .7rem;font-size:1.45rem}
.mo-story h3 a{color:inherit;text-decoration:none}
.mo-story h3 a:hover{color:var(--brand2-t)}
.mo-story p{margin:0 0 .6rem}
.mo-story .date{color:var(--brand2-t);letter-spacing:.16em}
.mo-more{margin:3rem 0 0;text-align:center}

/* ruled list that opens: dates, steps and questions */
.mo-acc{border:1px solid var(--rule);background:var(--bg)}
.mo-acc details,.mo-acc .mo-acc-row{border-bottom:1px solid var(--rule)}
.mo-acc>:last-child{border-bottom:0}
.mo-acc summary,.mo-acc .mo-acc-row{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1.15rem 1.25rem;font:800 .95rem/1.5 var(--body-font);letter-spacing:.05em;text-transform:uppercase;color:var(--brand2-t);cursor:pointer;list-style:none}
.mo-acc .mo-acc-row{cursor:default}
.mo-acc summary::-webkit-details-marker{display:none}
.mo-acc summary::after{content:"+";flex:none;font:300 2rem/1 var(--body-font);color:var(--brand2-t)}
.mo-acc details[open] summary::after{content:"\2013"}
.mo-acc details p{margin:0;padding:0 1.25rem 1.5rem;color:var(--text)}

/* the closing statement: large serif, centred, red buttons */
[data-theme=modern] .cta{background:var(--bg);color:var(--text);padding:clamp(4rem,10vw,7rem) 0}
[data-theme=modern] .cta .wrap{max-width:52rem}
[data-theme=modern] .cta h1,[data-theme=modern] .cta h2{max-width:24ch;margin:0 auto 1.5rem;color:var(--text);font:400 clamp(1.9rem,6vw,3.1rem)/1.28 var(--serif-font);letter-spacing:0;text-transform:none}
[data-theme=modern] .cta p{opacity:1}
[data-theme=modern] .cta .btns{justify-content:center;max-width:none;gap:1rem}
[data-theme=modern] .cta .btn{flex:0 0 auto;border:0;color:var(--brand2-ink)}
[data-theme=modern] .sec--quote{padding-top:clamp(2rem,6vw,4rem)}
[data-theme=modern] .quote{max-width:52rem;margin:0 auto;text-align:center}
[data-theme=modern] .quote blockquote{margin:0}
[data-theme=modern] .quote blockquote p{font:400 clamp(1.7rem,5vw,2.6rem)/1.3 var(--serif-font)}
[data-theme=modern] .quote figcaption{margin-top:1rem;font:800 .8rem/1.4 var(--body-font);letter-spacing:.24em;text-transform:uppercase;color:var(--brand2-t)}

/* contact, in the blue of the footer */
.mo-contact{display:grid;gap:2rem;margin-top:2.25rem;text-align:center;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr))}
.mo-contact p{margin:0;font-size:1.1rem}
.mo-contact b{display:block;font-weight:700;font-size:1.15rem}
.mo-contact a{color:inherit;text-decoration:none}
[data-theme=modern] .sec--navy h2{text-align:center}

/* photo wall: tidy squares */
[data-theme=modern] .photos{columns:auto;display:grid;grid-template-columns:repeat(2,1fr);gap:.5rem}
[data-theme=modern] .photo{margin:0;border-radius:0;aspect-ratio:1}
[data-theme=modern] .photo img{height:100%;object-fit:cover}
@media (min-width:44rem){[data-theme=modern] .photos{grid-template-columns:repeat(3,1fr);gap:.75rem}}
@media (min-width:64rem){[data-theme=modern] .photos{grid-template-columns:repeat(4,1fr)}}

/* footer: blue sheet, centred; a darker navy line of small print */
.ftr--modern{background:none;padding:0;text-align:center}
.mf-main{background:var(--ftr-bg) repeating-linear-gradient(135deg,rgba(255,255,255,.04) 0 2px,transparent 2px 18px);color:var(--ftr-ink);padding:clamp(3.5rem,8vw,5.5rem) 0 3.25rem}
.mf-brand{margin-bottom:3rem}
.mf-logo{display:block;margin:0 auto 1.5rem;width:fit-content}
.mf-logo img{display:block;height:clamp(5rem,22vw,6.5rem);width:auto;max-width:14rem;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.28))}
.mf-name{display:inline-block;padding-top:.5rem;border-top:1px solid color-mix(in srgb,currentColor 60%,transparent);font:500 clamp(1.6rem,6vw,2.4rem)/1.1 var(--serif-font);letter-spacing:.05em;text-transform:uppercase;color:inherit;text-decoration:none}
.mf-tag{margin:1rem auto 0;max-width:22rem;line-height:1.5;opacity:.85}
.mf-cols{display:grid;gap:2.75rem}
.mf-cols h2{margin:0 0 1.1rem;color:inherit;font:800 clamp(1.35rem,5vw,1.7rem)/1.1 var(--body-font);letter-spacing:0;text-transform:uppercase}
.mf-cols p{margin:0 0 1.1rem;font-size:1.05rem;line-height:1.45}
.mf-cols b{display:block;font-weight:700;font-size:1.1rem}
.mf-cols a{color:inherit;text-decoration:none}
.mf-cols a:hover{text-decoration:underline}
.mf-links{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:1fr 1fr;gap:.2rem 1rem}
.mf-links li{margin:.35rem 0}
@media (min-width:56rem){.mf-links{display:block}.mf-links li{margin:.55rem 0}}
.mf-links a{font:800 .92rem/1.3 var(--body-font);letter-spacing:.04em;text-transform:uppercase}
.mf-cols .btn{margin-top:.5rem}
.ftr--modern .social{justify-content:center;margin:3rem 0 0}
.ftr--modern .social a{width:3rem;height:3rem;border:2px solid currentColor;background:none}
.mf-legal{background:var(--brand);color:var(--hdr-ink);padding:2rem 0 2.5rem;font-size:.92rem;line-height:1.8}
.mf-legal p{margin:0}
.mf-legal a{color:inherit}
@media (min-width:56rem){.mf-cols{grid-template-columns:repeat(auto-fit,minmax(0,1fr));gap:3rem}}

/* inner pages: a navy title band with a slanted white foot, then plain content */
.mo-head{position:relative;background:var(--brand) linear-gradient(115deg,transparent 62%,rgba(255,255,255,.06) 62%);color:var(--hdr-ink);padding:clamp(2.5rem,7vw,4.5rem) 0 clamp(5.5rem,14vw,8.5rem)}
[data-theme=modern] .mo-head h1{max-width:17ch;margin:0;color:inherit}
.mo-kick{margin:0 0 .8rem;font:800 .85rem/1.4 var(--body-font);letter-spacing:.3em;text-transform:uppercase;opacity:.88}
.mo-kick--dark{color:var(--brand-t);opacity:1}
.mo-lead{max-width:38rem;margin:1.1rem 0 0;font-size:1.2rem;line-height:1.6;opacity:.94}
.mo-meta{margin:1.4rem 0 0;font:700 .78rem/1.5 var(--body-font);letter-spacing:.16em;text-transform:uppercase}
.mo-back{display:inline-block;margin-bottom:1.5rem;padding-bottom:.3rem;border-bottom:2px solid var(--brand2);color:inherit;font:700 .74rem/1 var(--body-font);letter-spacing:.2em;text-decoration:none;text-transform:uppercase}
.mo-head-cut{position:absolute;left:0;right:0;bottom:-1px;height:clamp(2.75rem,9vw,6rem);background:var(--bg);clip-path:polygon(0 100%,100% 100%,100% 0)}
.mo-body{padding:0 0 clamp(4rem,9vw,6.5rem)}
.mo-body--soft{padding-top:clamp(3rem,7vw,4.5rem);background:var(--soft)}
.mo-narrow{max-width:50rem}
.mo-cover{width:100%;aspect-ratio:16/9;object-fit:cover;margin:0 0 2.5rem}
[data-theme=modern] .mo-h2{margin:0 0 1.5rem}
.mo-tabs{display:flex;flex-wrap:wrap;gap:.5rem;margin:0 0 2.5rem}
.mo-tabs a{padding:.8rem 1.2rem;border:1px solid var(--brand2);color:var(--brand2-t);font:700 .72rem/1 var(--body-font);letter-spacing:.2em;text-decoration:none;text-transform:uppercase}
.mo-tabs a:hover{background:color-mix(in srgb,var(--brand2) 10%,var(--bg))}
.mo-tabs a[aria-current]{background:var(--brand2);color:var(--brand2-ink)}
.mo-cards{display:grid;gap:2.5rem 2.25rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,19rem),1fr))}
.mo-card{display:flex;flex-direction:column;background:var(--brand);color:var(--hdr-ink);clip-path:polygon(0 0,100% 0,100% calc(100% - 3.2rem),0 100%)}
.mo-card img{width:100%;aspect-ratio:3/2;object-fit:cover}
.mo-card-body{padding:1.75rem 1.5rem 5.2rem;text-align:center}
.mo-card .mo-kick{margin-bottom:.6rem;font-size:.74rem;color:var(--gold-lt);opacity:1}
[data-theme=modern] .mo-card h2{margin:0 0 .8rem;color:inherit;font-size:1.3rem}
.mo-card h2 a{color:inherit;text-decoration:none}
.mo-card p{font-size:.98rem;line-height:1.65;opacity:.92}
.mo-card time{font:700 .74rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase;opacity:.85}
.mo-albums{display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fill,minmax(min(100%,15rem),1fr))}
.mo-album{display:block;color:inherit;text-decoration:none}
.mo-album img,.mo-album-blank{display:block;width:100%;aspect-ratio:1;object-fit:cover;background:var(--brand)}
.mo-album-cap{display:block;padding:1rem 1.1rem 1.15rem;background:var(--brand);color:var(--hdr-ink);transition:background .2s}
.mo-album:hover .mo-album-cap{background:var(--brand2);color:var(--brand2-ink)}
.mo-album-cap small{display:block;margin-bottom:.3rem;font:700 .68rem/1.3 var(--body-font);letter-spacing:.2em;text-transform:uppercase;opacity:.85}
.mo-album-cap b{display:block;font:800 .98rem/1.3 var(--body-font);letter-spacing:.03em;text-transform:uppercase}
.mo-list{list-style:none;margin:0;padding:0;border:1px solid var(--rule);background:var(--bg)}
.mo-list li+li{border-top:1px solid var(--rule)}
.mo-list a{display:block;padding:1.1rem 1.25rem;font:800 .95rem/1.5 var(--body-font);letter-spacing:.04em;text-decoration:none;text-transform:uppercase;color:var(--brand2-t)}
.mo-list a:hover{background:var(--soft)}
.mo-list span{display:block;margin-bottom:.2rem;font:700 .68rem/1.3 var(--body-font);letter-spacing:.22em;color:var(--brand-t)}

/* forms and the red side panel */
.mo-split{display:grid;gap:3rem;align-items:start}
@media (min-width:56rem){.mo-split{grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:4rem}}
.mo-panel--side{--cut:3.4rem}
[data-theme=modern] .mo-panel--side h2{font-size:clamp(1.9rem,6vw,2.5rem)}
.mo-panel--side .mo-rows{margin-bottom:0}
.mo-note{margin:0 0 1.5rem;color:var(--muted);font-size:1rem}
.mo-field{display:block;margin:0 0 1.25rem}
.mo-field>span{display:block;margin-bottom:.5rem;font:800 .72rem/1.3 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--brand-t)}
.mo-field input,.mo-field textarea{display:block;width:100%;min-height:3.4rem;padding:.7rem 1rem;border:1px solid #b6bdc9;border-radius:0;background:#fff;color:var(--text);font:inherit;font-size:1rem}
.mo-field textarea{min-height:8rem;resize:vertical}
.mo-field input:focus,.mo-field textarea:focus{outline:0;border-color:var(--brand2);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand2) 22%,transparent)}
.mo-field .err{display:block;margin-top:.35rem;color:#b91c1c;font-size:.9rem;font-weight:600}
.mo-two{display:grid;gap:0 1.25rem}
@media (min-width:40rem){.mo-two{grid-template-columns:1fr 1fr}}
.mo-fine{margin:1.25rem 0 0;color:var(--muted);font-size:.9rem}
.mo-who{display:grid;grid-template-columns:repeat(3,1fr);margin:0 0 1.25rem;border:1px solid var(--brand)}
.mo-who label{position:relative;display:block;cursor:pointer}
.mo-who input{position:absolute;inset:0;opacity:0}
.mo-who span{display:block;padding:.95rem .4rem;text-align:center;font:700 .72rem/1.2 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--brand-t)}
.mo-who label+label{border-left:1px solid var(--brand)}
.mo-who input:checked+span{background:var(--brand);color:var(--brand-ink)}
.mo-who input:focus-visible+span{outline:3px solid var(--brand2);outline-offset:-3px}
.mo-thanks{padding:1rem 0}
.mo-search{display:flex;flex-wrap:wrap;gap:.6rem;margin:0 0 2rem}
.mo-search input{flex:1 1 14rem;min-height:3.6rem;padding:.7rem 1rem;border:1px solid #b6bdc9;border-radius:0;font:inherit;font-size:1.05rem}
.mo-search input:focus{outline:0;border-color:var(--brand2)}
.mo-results .result{border-color:var(--rule)}
[data-theme=modern] .mo-results .result h2{margin:.2rem 0 .4rem;font-size:1.1rem;letter-spacing:.02em}
.mo-results .result h2 a{color:var(--brand2-t)}
.mo-results .tag{color:var(--brand-t)}

/* testimonials: white tiles with a heavy navy quote mark and capital names */
[data-theme=modern] .tcard{border-radius:0;border:0;border-top:.3rem solid var(--brand2);background:#fff;box-shadow:0 18px 40px -26px rgba(10,20,60,.5)}
[data-theme=modern] .tcard::before{content:"\201C";font:700 4.5rem/.5 var(--head-font);color:var(--brand-t)}
[data-theme=modern] .tcard blockquote{font-family:var(--body-font);font-style:normal;font-size:1.15rem;line-height:1.65}
[data-theme=modern] .tcard-who b{font:700 .85rem/1.3 var(--body-font);letter-spacing:.14em;text-transform:uppercase;color:var(--brand-t)}
[data-theme=modern] .tsl-btn{border-radius:0}
[data-theme=modern] .sec--dark .tcard{background:#fff;color:var(--text)}
[data-theme=modern] .sec--dark .tcard-who span{color:var(--muted);opacity:1}
@endverbatim

/* ===== refinements: footer icons, back to top, sticky Apply, focus, tap size ===== */
.mf-lab{display:inline-flex;align-items:center;gap:.5rem}
.mf-lab svg{width:1.05rem;height:1.05rem;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;opacity:.85}
.mf-top{display:grid;place-items:center;width:3.25rem;height:3.25rem;margin:2.75rem auto 0;border:2px solid currentColor;color:inherit}
.mf-top svg{width:1.4rem;height:1.4rem}
.mf-top:hover{background:var(--brand2);border-color:var(--brand2);color:var(--brand2-ink)}
html:has(body[data-theme=modern]){scroll-behavior:smooth}
@media (prefers-reduced-motion:reduce){html:has(body[data-theme=modern]){scroll-behavior:auto}}
.mo-sticky{display:none}
@media (max-width:59.99rem){
  .mo-sticky{position:fixed;left:0;right:0;bottom:0;z-index:40;display:flex;align-items:center;justify-content:center;min-height:3.4rem;background:var(--brand2);color:var(--brand2-ink);font:700 .85rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;text-decoration:none;box-shadow:0 -8px 22px -10px rgba(0,0,0,.45);transform:translateY(110%);transition:transform .25s}
  body.scrolled .mo-sticky{transform:none}
  body.menu-open .mo-sticky,body.search-open .mo-sticky,body.cookie-open .mo-sticky{display:none}
  body[data-theme=modern]:has(.mo-sticky) .mf-legal{padding-bottom:5.5rem}
}
@media (prefers-reduced-motion:reduce){.mo-sticky{transition:none}}
[data-theme=modern] .hdr :focus-visible,[data-theme=modern] .menu :focus-visible,[data-theme=modern] .mf-main :focus-visible,[data-theme=modern] .mf-legal :focus-visible,[data-theme=modern] .hero :focus-visible,[data-theme=modern] .mo-head :focus-visible{outline-color:#fff}
[data-theme=modern] .sbtn,[data-theme=modern] .burger{min-width:3.25rem;min-height:3.25rem}
