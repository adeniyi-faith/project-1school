/* live sections: fees, term dates, team, events, downloads, message form (every theme; colours come from the theme variables) */
.live-grid{display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(min(100%,19rem),1fr))}
.live-card h3{margin:0 0 .75rem}
.live-table{width:100%;border-collapse:collapse;font-size:.98rem}
.live-table td{padding:.55rem 0;border-top:1px solid var(--line);vertical-align:top}
.live-table td:last-child{text-align:right;font-weight:700;white-space:nowrap;padding-left:1rem}
.live-table small{color:var(--muted);font-weight:400;margin-left:.3rem}
.live-total td{border-top:2px solid var(--line);font-weight:800}
.live-now{background:var(--brand);color:var(--brand-ink);border-radius:999px;padding:.1rem .5rem;font-size:.72rem}
.live-note{margin-top:1.25rem;color:var(--muted)}
.live-team{display:grid;gap:1.5rem;grid-template-columns:repeat(auto-fill,minmax(9.5rem,1fr));text-align:center}
.live-person img,.live-initial{width:7.5rem;height:7.5rem;border-radius:50%;object-fit:cover;margin:0 auto .75rem;display:grid;place-items:center;background:var(--brand);color:var(--brand-ink);font:700 2.2rem/1 var(--head-font)}
.live-person b{display:block}
.live-person span:not(.live-initial){display:block;color:var(--muted);font-size:.92rem}
.live-events{list-style:none;margin:0;padding:0;display:grid;gap:.75rem}
.live-events a{display:flex;gap:1.1rem;align-items:flex-start;padding:1rem 1.1rem;border:1px solid var(--line);border-radius:var(--radius);text-decoration:none;color:inherit}
.live-events a:hover{border-color:var(--brand)}
.live-events time{flex:none;width:4.2rem;text-align:center;background:var(--brand);color:var(--brand-ink);border-radius:var(--radius);padding:.5rem .25rem}
.live-events time b{display:block;font:700 1.7rem/1 var(--head-font)}
.live-events time span{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em}
.live-ev-body strong{display:block}
.live-ev-body small,.live-ev-body em{display:block;color:var(--muted);font-style:normal}
.live-files{list-style:none;margin:0;padding:0;display:grid;gap:.6rem}
.live-files a{display:flex;justify-content:space-between;gap:1rem;padding:.9rem 1.1rem;border:1px solid var(--line);border-radius:var(--radius);text-decoration:none;color:inherit;font-weight:600}
.live-files a:hover{border-color:var(--brand)}
.live-files small{color:var(--muted);white-space:nowrap}
.live-form{display:grid;gap:1rem;max-width:40rem}
.live-form label{display:grid;gap:.35rem;font-weight:600;font-size:.95rem}
.live-form input,.live-form textarea{width:100%;padding:.8rem .9rem;border:1px solid var(--line);border-radius:var(--radius);font:400 1rem/1.4 var(--body-font);background:var(--bg);color:inherit}
.live-form input:focus,.live-form textarea:focus{outline:2px solid var(--brand);outline-offset:1px}
.live-form .btn{justify-self:start}
.live-hp{position:absolute;left:-9999px;height:0;overflow:hidden}
.live-ok{padding:1rem 1.2rem;border:1px solid var(--line);border-left:4px solid var(--brand);border-radius:var(--radius);max-width:40rem}
.live-err{color:#b3261e;margin:0}
.chat-fab{position:fixed;right:1rem;bottom:1rem;z-index:70;display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 1.1rem;border-radius:999px;background:#128c4a;color:#fff;text-decoration:none;font:700 .95rem/1 var(--body-font);box-shadow:0 10px 26px -10px rgba(0,0,0,.55)}
.chat-fab svg{width:1.4rem;height:1.4rem;fill:currentColor}
@media (max-width:40rem){.chat-fab{bottom:4.6rem;padding:.8rem}.chat-fab span{display:none}}

/* newsletter sign-up above the footer (every theme; colours come from the theme variables) */
.sr-only{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
.fsu{background:var(--soft);color:var(--text);border-top:1px solid var(--line);padding:2.4rem 0}
.fsu-in{display:grid;gap:1.4rem;align-items:center}
.fsu h2{margin:0 0 .3rem;font-size:clamp(1.4rem,4.5vw,1.9rem)}
.fsu-text p{margin:0;color:var(--muted)}
.fsu-form{display:grid;gap:.7rem;min-width:0}
.fsu-row{display:flex;gap:.6rem;flex-wrap:wrap}
.fsu-label{flex:1 1 14rem;min-width:0;display:block}
.fsu-form input[type=email]{width:100%;min-height:3rem;padding:.7rem .9rem;border:1px solid var(--line);border-radius:var(--radius);background:var(--bg);color:var(--text);font:400 1rem/1.3 var(--body-font)}
.fsu-form input[type=email]:focus{outline:2px solid var(--brand);outline-offset:1px}
.fsu-consent{display:flex;gap:.55rem;align-items:flex-start;font-size:.88rem;line-height:1.45;color:var(--muted)}
.fsu-consent input{width:1.05rem;height:1.05rem;margin-top:.2rem;flex:none}
.fsu-consent a{color:inherit;text-decoration:underline}
.fsu-ok{margin:0}
@media(min-width:56rem){.fsu-in{grid-template-columns:1fr 1.2fr;gap:3rem}}

/* custom forms (every theme) */
.sf{max-width:44rem}
.sf-form{display:grid;gap:1.15rem}
.sf-grid{display:grid;gap:1rem;grid-template-columns:repeat(2,minmax(0,1fr))}
.sf-field{grid-column:1/-1;display:grid;gap:.35rem;min-width:0;align-content:start}
@media(min-width:40rem){.sf-field--half{grid-column:span 1}}
.sf-field>label,.sf-field legend{font-weight:600;font-size:.95rem;padding:0}
.sf-field fieldset{border:0;margin:0;padding:0;display:grid;gap:.45rem;min-width:0}
.sf-field em{color:var(--link);font-style:normal}
.sf-field input:not([type=checkbox]):not([type=radio]),.sf-field textarea,.sf-field select{width:100%;min-height:3rem;padding:.75rem .9rem;border:1px solid var(--line);border-radius:var(--radius);font:400 1rem/1.4 var(--body-font);background:var(--bg);color:inherit}
.sf-field textarea{min-height:8rem;resize:vertical}
.sf-field input:focus,.sf-field textarea:focus,.sf-field select:focus{outline:2px solid var(--brand);outline-offset:1px}
.sf-choice{display:flex;gap:.6rem;align-items:flex-start;font-weight:400;line-height:1.45;cursor:pointer}
.sf-choice input{width:1.1rem;height:1.1rem;margin-top:.22rem;flex:none;accent-color:var(--brand)}
.sf-help{color:var(--muted);font-size:.86rem}
.sf-err{color:#b3261e;font-size:.88rem}
.sf-consents{display:grid;gap:.7rem;padding-top:.3rem;border-top:1px solid var(--line)}
.sf-consents .sf-choice{font-size:.92rem;color:var(--muted)}
.sf-consents a{color:inherit;text-decoration:underline}
.sf-form .btn{justify-self:start}
