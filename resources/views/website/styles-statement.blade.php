@verbatim
/* ============ STATEMENT: its own menu and its own inner pages (sign-up, sign-in, news, gallery, search) ============ */
/* the phone menu: a deep maroon sheet with the ring pattern, gold roman numerals, ruled rows */
[data-theme=statement] .menu{flex-direction:column;background:var(--deep) var(--rings);border-top:4px solid var(--brand2);padding:2rem 1.5rem 3rem;color:#fff}
[data-theme=statement].menu-open .menu{display:flex}
[data-theme=statement] .menu-kick{margin:0 0 1.25rem;font:700 .7rem/1.5 var(--body-font);letter-spacing:.22em;text-transform:uppercase;color:var(--gold-lt)}
[data-theme=statement] .menu-list{list-style:none;margin:0;padding:0;border-top:1px solid rgba(255,255,255,.2)}
[data-theme=statement] .menu-list li{border-bottom:1px solid rgba(255,255,255,.2)}
[data-theme=statement] .menu-list a{display:flex;align-items:baseline;gap:1rem;padding:1.05rem 0;border:0;font:700 1.9rem/1.15 var(--head-font);color:#fff;white-space:normal;text-decoration:none}
[data-theme=statement] .menu-list a[aria-current]{color:var(--gold-lt);text-decoration:none}
[data-theme=statement] .menu-list b{flex:none;width:2.8rem;font:italic 600 1.15rem/1 var(--head-font);color:var(--brand2-d)}
[data-theme=statement] .menu-foot{display:grid;gap:.9rem;margin-top:2rem}
[data-theme=statement] .menu-foot .menu-signin{display:flex;align-items:center;justify-content:center;min-height:3.5rem;border:2px solid rgba(255,255,255,.7);border-radius:.3rem;font:600 1.05rem/1 var(--body-font);color:#fff}
[data-theme=statement] .menu-foot .btn{display:flex;justify-content:center;align-items:center;margin:0;width:100%;min-height:3.5rem;background:var(--brand2);border-color:var(--brand2);color:var(--deep);font:700 1.05rem/1 var(--body-font);border-radius:.3rem}
@media (min-width:72rem){
  [data-theme=statement] .menu,[data-theme=statement].menu-open .menu{flex-direction:row;align-items:center;background:none;border:0;padding:0;color:var(--text)}
  [data-theme=statement] .menu-kick{display:none}
  [data-theme=statement] .menu-list{display:flex;align-items:center;border:0}
  [data-theme=statement] .menu-list li{border:0}
  [data-theme=statement] .menu-list a{display:block;padding:.7rem .6rem;font:700 .74rem/1 var(--body-font);letter-spacing:.1em;text-transform:uppercase;color:var(--text);border-bottom:3px solid transparent}
  [data-theme=statement] .menu-list a:hover{border-bottom-color:var(--brand2)}
  [data-theme=statement] .menu-list a[aria-current]{border-bottom-color:var(--brand);color:var(--brand-t)}
  [data-theme=statement] .menu-list b{display:none}
  [data-theme=statement] .menu-foot{display:flex;align-items:center;gap:.25rem;margin:0 0 0 .5rem}
  [data-theme=statement] .menu-foot .menu-signin{display:block;min-height:0;border:0;padding:.7rem .6rem;font:700 .74rem/1 var(--body-font);letter-spacing:.1em;text-transform:uppercase;color:var(--text)}
  [data-theme=statement] .menu-foot .btn{display:inline-flex;width:auto;min-height:2.5rem;padding:.65rem 1.3rem;background:var(--brand);border-color:var(--brand);color:var(--brand-ink);font:700 .74rem/1 var(--body-font);letter-spacing:.08em;text-transform:uppercase}
}

/* inner pages: gold label, big maroon serif title, plain intro, boxed fields with a gold edge, a deep band below */
.sp-intro{padding:clamp(3.5rem,9vw,5.5rem) 0 1.25rem;background:var(--bg)}
.sp-kicker{margin:0 0 .9rem;font:700 .8rem/1.6 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--brand2-t)}
.sp-intro h1{color:var(--brand-t);font-size:clamp(2.4rem,10vw,4.4rem);line-height:1.06;margin:0 0 .45em;max-width:19ch}
.sp-lead{max-width:42rem;margin:0 0 1rem;font:400 1.125rem/1.75 var(--body-font);color:var(--text)}
.sp-back{display:inline-block;margin-bottom:1.5rem;font:700 .78rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--brand-t);text-decoration:none;border-bottom:2px solid var(--brand2);padding-bottom:.35rem}
.sp-date{display:block;margin-top:1rem;font:600 .78rem/1 var(--body-font);letter-spacing:.16em;text-transform:uppercase;color:var(--olive)}
.sp-body{padding:1.25rem 0 clamp(4rem,9vw,6rem);background:var(--bg)}
.sp-narrow>*{max-width:44rem}
.sp-narrow>img,.sp-narrow>.sp-cover{max-width:100%}
.sp-note{margin:0 0 1.25rem;color:var(--olive);font-size:1rem;line-height:1.6}
.sp-form{max-width:36rem}
.sp-field{display:block;margin:0 0 .9rem}
.sp-field input,.sp-field select,.sp-field textarea{display:block;width:100%;min-height:3.5rem;padding:.85rem 1rem;background:#fff;border:1px solid #e6dccb;border-left:4px solid var(--brand2);border-radius:.15rem;color:var(--brand-t);font:500 1rem/1.4 var(--body-font)}
.sp-field textarea{min-height:8rem;resize:vertical}
.sp-field input::placeholder,.sp-field textarea::placeholder{color:var(--brand-t);opacity:.85}
.sp-field input:focus,.sp-field select:focus,.sp-field textarea:focus{outline:0;border-color:var(--brand);border-left-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 15%,transparent)}
.sp-field .err{display:block;margin-top:.3rem;color:#b91c1c;font-weight:600;font-size:.9rem}
.sp-form .btn--solid{margin-top:.6rem;min-height:3.5rem;padding:0 2rem;border-radius:.3rem;font:700 1rem/1 var(--body-font)}
.sp-fine{margin:1.1rem 0 0;color:var(--olive);font-size:.92rem;line-height:1.65}
.sp-who{display:flex;gap:1.75rem;margin:0 0 1.25rem;border-bottom:1px solid #e6dccb}
.sp-who label{position:relative;cursor:pointer}
.sp-who input{position:absolute;opacity:0;inset:0}
.sp-who span{display:block;padding:.75rem 0;margin-bottom:-1px;border-bottom:3px solid transparent;font:700 .78rem/1 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--olive)}
.sp-who input:checked+span{color:var(--brand-t);border-bottom-color:var(--brand2)}
.sp-who input:focus-visible+span{outline:3px solid var(--brand);outline-offset:2px}
.sp-thanks h2{color:var(--brand-t);font-size:clamp(1.9rem,7vw,2.6rem)}
.sp-band{padding:clamp(4rem,9vw,6rem) 0;background:var(--deep) var(--rings);color:#fff;border-top:4px solid var(--brand2)}
.sp-band h2{color:#fff;font-size:clamp(2rem,8vw,3.2rem);margin-bottom:1.5rem;max-width:20ch}
.sp-steps{list-style:none;margin:0;padding:0;border-top:1px solid rgba(255,255,255,.22)}
.sp-steps li{display:grid;grid-template-columns:3.6rem 1fr;gap:0 1rem;padding:1.6rem 0;border-bottom:1px solid rgba(255,255,255,.22)}
.sp-steps b{font:700 2.6rem/1 var(--head-font);color:var(--brand2-d)}
.sp-steps h3{margin:.2rem 0 .35rem;font:700 1.15rem/1.35 var(--body-font);color:#fff;letter-spacing:0}
.sp-steps p{margin:0;color:rgba(255,255,255,.86)}
@media (min-width:60rem){.sp-steps{display:grid;grid-template-columns:repeat(3,1fr);column-gap:3rem;border-top:0}.sp-steps li{display:block;border-top:1px solid rgba(255,255,255,.22);border-bottom:0}.sp-steps b{display:block;margin-bottom:.8rem}}
/* news, albums */
.sp-tabs{display:flex;flex-wrap:wrap;gap:.2rem 1.6rem;margin-top:.5rem}
.sp-tabs a{padding:.5rem 0;border-bottom:3px solid transparent;font:700 .76rem/1 var(--body-font);letter-spacing:.18em;text-transform:uppercase;color:var(--olive);text-decoration:none}
.sp-tabs a:hover,.sp-tabs a[aria-current]{color:var(--brand-t);border-bottom-color:var(--brand2)}
.sp-cards{display:grid;gap:1.5rem;grid-template-columns:1fr}
@media (min-width:44rem){.sp-cards{grid-template-columns:repeat(2,1fr)}}
@media (min-width:64rem){.sp-cards{grid-template-columns:repeat(3,1fr)}}
.sp-card{display:flex;flex-direction:column;background:#fff;border:1px solid #e8e0cf;border-bottom:5px solid var(--brand2);border-radius:.15rem;color:var(--text);text-decoration:none}
.sp-card img{width:100%;aspect-ratio:16/10;object-fit:cover}
.sp-card-body{display:flex;flex-direction:column;gap:.35rem;padding:1.6rem 1.6rem 1.7rem}
.sp-card h2,.sp-card b{margin:0;font:700 1.55rem/1.2 var(--head-font);color:var(--brand-t)}
.sp-card h2 a{color:inherit;text-decoration:none}
.sp-card h2 a:hover{text-decoration:underline}
.sp-card p{margin:.2rem 0 0;color:var(--text);font-size:1rem;line-height:1.65}
.sp-card .sp-kicker{margin:0 0 .3rem}
.sp-card .sp-date{margin-top:.9rem}
a.sp-card:hover b{text-decoration:underline}
/* a story */
.sp-cover{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;margin:1.25rem 0 0;border-top:5px solid var(--brand2)}
.sp-facts{display:flex;flex-wrap:wrap;gap:1rem 2.5rem;margin:1.5rem 0 0;padding:1.25rem 1.5rem;background:#fff;border-top:5px solid var(--brand2);box-shadow:0 18px 40px -26px rgba(0,0,0,.4)}
.sp-facts dt{font:700 .72rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--olive);margin-bottom:.35rem}
.sp-facts dd{margin:0;font:700 1.35rem/1.25 var(--head-font);color:var(--brand-t)}
.sp-more{list-style:none;margin:0;padding:0;border-top:1px solid rgba(255,255,255,.22)}
.sp-more li{border-bottom:1px solid rgba(255,255,255,.22)}
.sp-more a{display:block;padding:1.3rem 0;font:700 1.55rem/1.25 var(--head-font);color:#fff;text-decoration:none}
.sp-more a:hover{color:var(--gold-lt)}
.sp-more span{display:block;margin-bottom:.35rem;font:700 .72rem/1 var(--body-font);letter-spacing:.2em;text-transform:uppercase;color:var(--brand2-t)}
[data-theme=statement] .photo{border-radius:0;border-bottom:4px solid var(--brand2)}
/* search */
.sp-search{display:flex;gap:.6rem;max-width:36rem;margin-top:.5rem}
.sp-search input{flex:1;min-width:0;min-height:3.5rem;padding:.85rem 1rem;background:#fff;border:1px solid #e6dccb;border-left:4px solid var(--brand2);border-radius:.15rem;color:var(--brand-t);font:500 1rem/1.4 var(--body-font)}
.sp-search input:focus{outline:0;border-color:var(--brand);border-left-color:var(--brand)}
.sp-search .btn{min-height:3.5rem;padding:0 1.5rem;border-radius:.3rem}
.sp-results .result{border-color:#e6dccb}
.sp-results .tag{color:var(--brand2-t);letter-spacing:.2em}
.sp-results .result h2{font:700 1.55rem/1.2 var(--head-font);color:var(--brand-t)}
.sp-results .result p{color:var(--text)}
.sp-results .result mark{background:var(--gold-lt);color:var(--deep)}

/* testimonials: plain cards with a gold bar down the side, big serif words */
[data-theme=statement] .tcard{border-radius:.5rem;border:0;border-left:.35rem solid var(--brand2);background:#fff}
[data-theme=statement] .tcard blockquote{font-family:var(--head-font);font-size:clamp(1.25rem,3.4vw,1.6rem)}
[data-theme=statement] .tcard-who b{font-family:var(--head-font);font-size:1.15rem;color:var(--brand-t)}
[data-theme=statement] .sec--dark .tcard{background:rgba(255,255,255,.07);border-left-color:var(--brand2-d)}
@media (max-width:59.99rem){[data-theme=statement] .menu .menu-sub{margin-left:3.4rem}}
@endverbatim
