{{-- The slide-in menu rules for the Grove theme, written once and used for phones and for laptops where the menu is too long for the bar. $p is the body selector they apply under. --}}
{!! $p !!} .menu{display:flex;flex-direction:column;position:fixed;inset:0 0 0 auto;width:min(88vw,25rem);padding:0;background:var(--deep);color:#fff;z-index:70;overflow-y:auto;overscroll-behavior:contain;transform:translateX(104%);visibility:hidden;transition:transform .4s var(--ease),visibility 0s .4s;box-shadow:-40px 0 70px -40px rgba(0,0,0,.6)}
body.menu-open{!! $p !!} .menu{transform:none;visibility:visible;transition:transform .4s var(--ease)}
{!! $p !!} .gv-drawer-top{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.35rem 1.15rem 1.6rem;border-bottom:1px solid rgba(255,255,255,.16)}
{!! $p !!} .gv-drawer-name{font:600 .8rem/1.3 var(--head-font);letter-spacing:.12em;text-transform:uppercase;color:color-mix(in srgb,var(--brand2) 75%,#fff)}
{!! $p !!} .gv-x{display:grid;place-items:center;flex:none;width:2.8rem;height:2.8rem;border:1px solid rgba(255,255,255,.3);border-radius:50%;background:none;color:#fff;cursor:pointer;transition:background .2s}
{!! $p !!} .gv-x:hover{background:rgba(255,255,255,.14)}
{!! $p !!} .gv-x svg{width:1.25rem;height:1.25rem}
{!! $p !!} .menu .menu-list{padding:.6rem 1.6rem 1rem}
{!! $p !!} .menu .menu-item>a:not(.btn){display:block;padding:1rem 0;border-bottom:1px solid rgba(255,255,255,.14);font:500 1.6rem/1.2 var(--head-font);letter-spacing:-.01em;color:#fff;white-space:normal}
{!! $p !!} .menu .menu-item>a:not(.btn):hover,{!! $p !!} .menu .menu-item>a[aria-current]{color:color-mix(in srgb,var(--brand2) 75%,#fff);text-decoration:none}
{!! $p !!} .menu .menu-sub{margin:.2rem 0 .6rem;border-left-color:color-mix(in srgb,var(--brand2) 55%,transparent)}
{!! $p !!} .menu .menu-item>.menu-sub a{color:#fff;font:400 1.05rem/1.3 var(--body-font);padding:.6rem .85rem;border:0}
{!! $p !!} .menu .menu-signin{font:400 1.1rem/1.2 var(--body-font)!important;opacity:.85}
{!! $p !!} .gv-apply{margin-top:1.1rem}
{!! $p !!} .menu .gv-apply .btn{display:flex;width:100%;margin:0;min-height:3.4rem;border:0;background:var(--fill);color:var(--fill-ink);font-size:1.02rem}
{!! $p !!} .gv-drawer-foot{display:grid;grid-template-columns:minmax(0,1fr);gap:.5rem;margin-top:auto;padding:1.4rem 1.6rem 2rem;border-top:1px solid rgba(255,255,255,.16);font-size:.98rem}
{!! $p !!} .menu .gv-drawer-foot>a{display:block;padding:.2rem 0;border:0;font:400 1rem/1.5 var(--body-font);color:#fff;text-decoration:none;overflow-wrap:anywhere}
{!! $p !!} .menu .gv-drawer-foot .social li a{display:grid;place-items:center;width:2.75rem;height:2.75rem;padding:0;border:0;font-size:1rem;background:rgba(255,255,255,.14)}
{!! $p !!} .gv-drawer-foot .social{margin:.6rem 0 0}
{!! $p !!} .gv-burger{display:inline-flex}
{!! $p !!} .gv-scrim{display:block}
{!! $p !!} .menu .menu-list{display:block}
{!! $p !!} .menu .menu-item{display:block;position:static}
{!! $p !!} .menu .gv-apply{margin-left:0}
{!! $p !!} .menu .menu-item>a:not(.btn){border-radius:0;opacity:1;text-transform:none;background:none}
{!! $p !!} .menu .menu-sub{display:block;position:static;min-width:0;padding:0 0 0 1rem;border-radius:0;background:none;box-shadow:none}
{!! $p !!} .menu .menu-item>.menu-sub a{text-transform:none;letter-spacing:0;white-space:normal}
{!! $p !!} .menu .menu-item>.menu-sub a:hover{background:none;text-decoration:underline}
