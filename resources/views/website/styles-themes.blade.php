@verbatim
/* ===== brand-name small line (used by Statement) ===== */
.brand small{display:block;margin-top:.25rem;font:700 .68rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;opacity:.75;white-space:normal}
.hero em,h1 em,h2 em{font-style:italic}
.wave{position:absolute;left:0;right:0;bottom:-1px;width:100%;height:3.5rem;fill:var(--bg);display:block}

.hero-grid{display:grid;gap:2.5rem;align-items:center}
@media (min-width:56rem){.hero-grid{grid-template-columns:1.12fr .88fr;gap:3rem}}

/* ================= STATEMENT: maroon and gold civic look. Fraunces headlines, Instrument Sans text, ring pattern, flat deep bands ================= */
[data-theme=statement]{--hdr-h:4.9rem;--rings:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='92' height='92'%3E%3Ccircle cx='46' cy='46' r='10' fill='none' stroke='%23ff9a9a' stroke-opacity='.2' stroke-width='1.6'/%3E%3C/svg%3E");--olive:#6b6a58;--pink:#ecd6d6}
[data-theme=statement] body,body[data-theme=statement]{overflow-x:clip;font-size:1rem;line-height:1.8}
[data-theme=statement] .wrap{padding:0 1.5rem}
[data-theme=statement] p{text-wrap:wrap}
[data-theme=statement] .hdr-in{position:relative}
@media (max-width:59.99rem){[data-theme=statement] .hdr-tools{position:absolute;right:.45rem;top:50%;transform:translateY(-50%)}}
[data-theme=statement] .brand{flex:1 1 auto;padding-right:6.4rem}
[data-theme=statement] h1,[data-theme=statement] h2,[data-theme=statement] h3{font-weight:700;letter-spacing:-.015em;font-optical-sizing:auto}
[data-theme=statement] .notice{background:var(--brand2);color:var(--deep)}
[data-theme=statement] .notice p{font:600 .9rem/1.55 var(--body-font)}
[data-theme=statement] .hdr{background:var(--bg);box-shadow:none;border-bottom:3px solid var(--brand);color:var(--brand-t)}
[data-theme=statement] .hdr-in{min-height:4.7rem;padding-top:1rem;padding-bottom:1rem}
[data-theme=statement] .brand{flex-direction:row;align-items:center;gap:.8rem}
[data-theme=statement] .brand span{display:block;min-width:0}
[data-theme=statement] .brand img{flex:none;width:auto;max-width:4rem;height:3rem;margin:0;object-fit:contain}
[data-theme=statement] .brand span{font:700 1.2rem/1.2 var(--head-font);letter-spacing:0;color:var(--hdr-name);overflow:visible;text-overflow:clip}
[data-theme=statement] .brand small{margin-top:.3rem;color:var(--olive);opacity:1;font:600 .62rem/1.25 var(--body-font);letter-spacing:.16em}
[data-theme=statement] .sbar-in{padding-top:1rem;padding-bottom:1rem}
@media (max-width:36rem){[data-theme=statement] .brand small{display:none}}
[data-theme=statement] .burger{color:var(--brand-t)}
[data-theme=statement] .menu a{font-family:var(--head-font);font-weight:600;white-space:nowrap}
@media (min-width:72rem){
  [data-theme=statement] .brand{padding-right:0}
  [data-theme=statement] .menu{flex:none}
  [data-theme=statement] .menu a{font:700 .74rem/1 var(--body-font);letter-spacing:.1em;text-transform:uppercase;border-radius:0;padding:.7rem .6rem;border-bottom:3px solid transparent;color:var(--text)}
  [data-theme=statement] .menu a:hover{background:none;border-bottom-color:var(--brand2)}
  [data-theme=statement] .menu a[aria-current]{background:none;border-bottom-color:var(--brand);color:var(--brand-t)}
  [data-theme=statement] .menu .btn{border-radius:.3rem;border-bottom:0;letter-spacing:.06em;padding:.65rem 1.3rem;color:var(--brand-ink)}
}

/* hero: ring pattern on a maroon gradient, with an optional white details card */
.hero--statement{display:block;min-height:0;color:#fff;background:var(--rings) 0 0/92px 92px,linear-gradient(180deg,var(--band) 0%,var(--deep) 100%)}
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
.fact-v{margin:0;font:700 1.5rem/1.2 var(--head-font);color:var(--brand-t)}
.hero-card .btns{flex-direction:column;align-items:flex-start;gap:.8rem;margin-top:2.2rem}
[data-theme=statement] .hero .btn{min-height:3.75rem;padding:0 1.85rem;border-radius:.3rem;font:600 1.0625rem/1.2 var(--body-font);box-shadow:none}
[data-theme=statement] .hero-card .btn--solid{background:var(--brand);color:var(--brand-ink)}
[data-theme=statement] .hero-card .btn--ghost{background:#fff;color:var(--brand-t);border:2px solid var(--brand)}
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
[data-theme=statement] .action-ico{background:none;border-radius:0;color:var(--brand-t);width:2.6rem}

/* sections */
[data-theme=statement] .sec{padding:6.25rem 0}
@media (min-width:60rem){[data-theme=statement] .sec{padding:7rem 0}}
[data-theme=statement] .sec.page-head{padding-bottom:1rem}
[data-theme=statement] .sec.sec--tight{padding-top:1rem}
[data-theme=statement] .sec--quote{padding-top:0}
[data-theme=statement] .sec:has(+ .sec--quote){padding-bottom:1rem}
[data-theme=statement] .eyebrow,[data-theme=statement] .prose .eyebrow{margin:0 0 .9rem;font:700 .78rem/1.6 var(--body-font);letter-spacing:.23em;color:var(--brand2-t)}
[data-theme=statement] .prose .eyebrow+h1,[data-theme=statement] .prose .eyebrow+h2{margin-top:0}
[data-theme=statement] .eyebrow::before{display:none}
[data-theme=statement] .sec-head{max-width:52rem;margin-bottom:2.4rem}
[data-theme=statement] .sec-head p:not(.eyebrow){color:var(--text);font-size:1.0625rem;line-height:1.7}
[data-theme=statement] h1,[data-theme=statement] h2,[data-theme=statement] h3{text-wrap:wrap}
[data-theme=statement] .sec h1,[data-theme=statement] h2,[data-theme=statement] .prose h2{font-size:clamp(2rem,10vw,3.8rem);line-height:1.1;color:var(--brand-t)}
[data-theme=statement] .sec--dark h1{color:#fff}
[data-theme=statement] .prose p{max-width:44rem;color:var(--text);font-size:1.0625rem;line-height:1.7}
[data-theme=statement] .prose h3{font-size:clamp(1.45rem,6.4vw,2.2rem);line-height:1.25;color:var(--brand-t);margin:3.2rem 0 1.1rem}
[data-theme=statement] .prose>div>h3:first-child,[data-theme=statement] .prose h3:first-child{margin-top:0}
[data-theme=statement] .prose a{color:var(--brand-t);font-weight:600}
[data-theme=statement] .prose ul{max-width:none;margin:0 0 1.5rem}
[data-theme=statement] .prose ul li{margin:0;padding:.75rem 0 .75rem 1.65rem;border-bottom:1px solid var(--pink);font-size:1.0625rem;line-height:1.7}
[data-theme=statement] .prose ul li::before{content:"";position:absolute;left:.05rem;top:1.2rem;width:.55rem;height:.55rem;border-radius:.15rem;background:var(--brand2-t)}
[data-theme=statement] .prose ol{max-width:44rem}
[data-theme=statement] .sec--soft,[data-theme=statement] .sec--tint{background:var(--soft);border-top:1px solid var(--pink)}
[data-theme=statement] .sec--tint{border-top:0;background:#eeeae4}
[data-theme=statement] .sec--soft+.sec:not(.sec--soft){border-top:1px solid var(--pink)}

/* deep colour bands */
[data-theme=statement] .sec--dark{background:var(--rings) 0 0/92px 92px,var(--band);color:#fff;border:0}
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
[data-theme=statement] .sec:not(.sec--dark) .step-t h3{color:var(--brand-t)}
[data-theme=statement] .sec:not(.sec--dark) .step-n{color:var(--brand2-d)}

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
.quote p{margin:0;font:italic 400 1.375rem/1.4 var(--head-font);color:var(--brand-t)}
.quote figcaption{margin-top:1.4rem;font:700 .78rem/1.7 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--olive)}
@media (min-width:48rem){.quote p{font-size:2rem}}

/* person profile */
.profile{max-width:44rem;margin:0 auto;background:#6b1414;background:var(--band);color:#fff;box-shadow:0 30px 60px -34px rgba(0,0,0,.5)}
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
.pillar-n{margin:0 0 1.2rem!important;font:700 .74rem/1 var(--body-font);letter-spacing:.22em;text-transform:uppercase;color:var(--brand2-t)!important}
.pillar h3{margin:0 0 1.1rem;font-size:1.6rem;line-height:1.2;color:var(--brand-t)}
.pillar p:last-child{margin:0;color:var(--text);font-size:1rem;line-height:1.7}
.cards--panels{grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr));gap:1.6rem}
.panel-card{background:#fff;border:1px solid var(--pink);border-top:.4rem solid var(--brand);border-radius:.5rem;box-shadow:0 22px 44px -32px rgba(60,20,20,.35)}
.panel-card:nth-child(even){border-top-color:var(--brand2)}
.panel-card:hover{transform:none}
.panel-card .card-body{padding:2.4rem 2.5rem 2.6rem}
.panel-card h3{margin:0 0 1.2rem;font-size:1.75rem;line-height:1.2;color:var(--brand-t)}
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
[data-theme=statement] .pagehero h1{color:var(--brand-t);max-width:20ch;font-size:clamp(2.2rem,9.7vw,3.7rem)}
[data-theme=statement] .hero--statement .hero-kicker,[data-theme=statement] .hero--statement .eyebrow,[data-theme=statement] .hero--statement .hero-tag{color:var(--brand2-d)}
[data-theme=statement] .pagehero .hero-tag{display:block;padding:0;border:0;background:none;color:var(--brand2-d);font:700 .8rem/1.6 var(--body-font);letter-spacing:.24em;text-transform:uppercase;-webkit-backdrop-filter:none;backdrop-filter:none}
[data-theme=statement] .pagehero .hero-tag i{display:none}
[data-theme=statement] .pagehero p{opacity:1;color:var(--text)}
[data-theme=statement] .formwrap{margin-top:0;background:var(--soft);padding-top:1rem}
[data-theme=statement] .panel{border-radius:.5rem;border:1px solid var(--pink);border-top:.4rem solid var(--brand2);box-shadow:none;background:#fff}
[data-theme=statement] .panel--soft{background:var(--bg)}
[data-theme=statement] .field>span{color:var(--brand-t);font-weight:600}
[data-theme=statement] .field input,[data-theme=statement] .field select,[data-theme=statement] .field textarea{background:#fff;border:1px solid #e6d3d1;border-left:.3rem solid var(--brand2);border-radius:.4rem;min-height:3.4rem}
[data-theme=statement] .btn--solid{border-radius:.3rem}
@endverbatim
