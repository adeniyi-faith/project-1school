@verbatim
/* ============ cookie notice: one box, dressed differently by every theme ============ */
.cookie{position:fixed;z-index:90;left:.75rem;right:.75rem;bottom:calc(.75rem + env(safe-area-inset-bottom));max-width:26rem;background:var(--bg);color:var(--text);border-radius:var(--radius-lg);box-shadow:0 24px 60px -18px rgba(0,0,0,.5),0 0 0 1px var(--line);animation:cookie-in .45s cubic-bezier(.2,.8,.2,1) both}
.cookie[hidden]{display:none}
.cookie-in{padding:1.4rem 1.5rem 1.5rem}
.cookie-title{margin:0 0 .45rem;font:600 1.25rem/1.2 var(--head-font);color:var(--link)}
.cookie-text{margin:0 0 1.1rem;font-size:.95rem;line-height:1.6}
.cookie-text a{color:var(--link);font-weight:600}
.cookie-ok{display:block;width:100%;min-height:3rem;padding:0 1.5rem;border:2px solid var(--brand);border-radius:var(--btn-radius);background:var(--brand);color:var(--brand-ink);font:600 1rem/1 var(--body-font);cursor:pointer;transition:filter .15s}
.cookie-ok:hover{filter:brightness(1.12)}
@keyframes cookie-in{from{opacity:0;transform:translateY(1.25rem)}to{opacity:1;transform:none}}
@media (min-width:40rem){.cookie{left:1.5rem;right:auto;bottom:1.5rem}}

/* Heritage: white card, thick gold top edge, outlined capital button */
[data-theme=heritage] .cookie{border-radius:var(--radius);border-top:5px solid var(--brand2);box-shadow:0 24px 60px -18px rgba(0,0,0,.45)}
[data-theme=heritage] .cookie-title{font-family:var(--head-font);font-size:1.5rem;font-weight:600;color:var(--brand-t)}
[data-theme=heritage] .cookie-ok{background:transparent;color:var(--brand-t);border-color:var(--brand);border-radius:.75rem;font-size:.85rem;letter-spacing:.16em;text-transform:uppercase}
[data-theme=heritage] .cookie-ok:hover{background:var(--brand);color:var(--brand-ink);filter:none}

/* Statement: deep sheet, gold button */
[data-theme=statement] .cookie{background:var(--deep);color:var(--deep-ink);border-radius:.4rem;box-shadow:0 24px 60px -18px rgba(0,0,0,.6);border-left:4px solid var(--brand2)}
[data-theme=statement] .cookie-title{font-family:var(--head-font);font-size:1.45rem;font-weight:600;color:#fff}
[data-theme=statement] .cookie-text a{color:var(--gold-lt)}
[data-theme=statement] .cookie-ok{background:var(--brand2);border-color:var(--brand2);color:var(--brand2-ink);border-radius:.4rem}

/* Modern: a navy bar across the bottom with a red square button */
[data-theme=modern] .cookie{left:0;right:0;bottom:0;max-width:none;border-radius:0;box-shadow:0 -12px 40px -12px rgba(0,20,45,.45);background:var(--brand);color:var(--hdr-ink)}
[data-theme=modern] .cookie-in{display:grid;gap:.9rem;max-width:72rem;margin:0 auto;padding:1.35rem 1.25rem calc(1.35rem + env(safe-area-inset-bottom))}
[data-theme=modern] .cookie-title{margin:0;font:800 .8rem/1 var(--body-font);letter-spacing:.3em;text-transform:uppercase;color:inherit}
[data-theme=modern] .cookie-text{margin:0;color:inherit}
[data-theme=modern] .cookie-text a{color:inherit}
[data-theme=modern] .cookie-ok{background:var(--brand2);border-color:var(--brand2);color:var(--brand2-ink);border-radius:0;font:500 .8rem/1 var(--body-font);letter-spacing:.26em;text-transform:uppercase}
@media (min-width:56rem){[data-theme=modern] .cookie-in{grid-template-columns:auto 1fr auto;align-items:center;gap:2rem}[data-theme=modern] .cookie-ok{width:auto;padding:0 2.4rem}}

/* Warm: white card, orange edge, maroon button */
[data-theme=warm] .cookie{border-radius:1.5rem;border:2px solid var(--brand2);box-shadow:0 26px 60px -20px rgba(60,15,25,.5)}
[data-theme=warm] .cookie-title{font-family:var(--head-font);font-weight:600;color:var(--brand-t)}
[data-theme=warm] .cookie-ok{background:var(--brand);border-color:var(--brand);color:var(--brand-ink);border-radius:.35rem;font-size:.8rem;letter-spacing:.18em;text-transform:uppercase}
[data-theme=warm] .cookie-ok:hover{background:var(--brand2);border-color:var(--brand2);color:var(--brand2-ink);filter:none}
@media print{.cookie{display:none}}

/* Crest: flat white card with a thick coral top edge, capital title, chevron-free flat button */
[data-theme=crest] .cookie{border-radius:0;border-top:6px solid var(--brand2);box-shadow:0 22px 50px -20px rgba(0,0,0,.5)}
[data-theme=crest] .cookie-title{font-family:var(--head-font);font-weight:800;font-size:1.15rem;text-transform:uppercase;letter-spacing:.06em;color:var(--brand-t)}
[data-theme=crest] .cookie-ok{background:var(--band);border-color:var(--band);color:#fff;border-radius:0;font-size:.8rem;font-weight:700;letter-spacing:.24em;text-transform:uppercase}
[data-theme=crest] .cookie-ok:hover{background:var(--fill);border-color:var(--fill);color:var(--fill-ink);filter:none}
@endverbatim
@verbatim
[data-theme=evergreen] .cookie{border-radius:0;border:1px solid var(--brand);box-shadow:0 22px 50px -20px rgba(10,30,20,.5)}
[data-theme=evergreen] .cookie-title{font-family:var(--head-font);font-weight:400;color:var(--brand-t)}
[data-theme=evergreen] .cookie-ok{background:var(--brand);border-color:var(--brand);color:var(--brand-ink);border-radius:0;font-size:.78rem;letter-spacing:.18em;text-transform:uppercase}
[data-theme=evergreen] .cookie-ok:hover{background:var(--brand2);border-color:var(--brand2);color:var(--brand2-ink);filter:none}

/* Crest: flat white card with a thick coral top edge, capital title, chevron-free flat button */
[data-theme=crest] .cookie{border-radius:0;border-top:6px solid var(--brand2);box-shadow:0 22px 50px -20px rgba(0,0,0,.5)}
[data-theme=crest] .cookie-title{font-family:var(--head-font);font-weight:800;font-size:1.15rem;text-transform:uppercase;letter-spacing:.06em;color:var(--brand-t)}
[data-theme=crest] .cookie-ok{background:var(--band);border-color:var(--band);color:#fff;border-radius:0;font-size:.8rem;font-weight:700;letter-spacing:.24em;text-transform:uppercase}
[data-theme=crest] .cookie-ok:hover{background:var(--fill);border-color:var(--fill);color:var(--fill-ink);filter:none}
@endverbatim
