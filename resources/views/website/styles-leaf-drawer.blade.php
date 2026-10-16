{{-- The slide-in menu rules for the Leaf theme, written once and used for phones and for laptops where the menu is too long for the bar. $p is the body selector they apply under. --}}
{!! $p !!} .menu{display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;width:min(88vw,26rem);padding:0;background:#fff;color:var(--text);z-index:70;overflow-y:auto;overscroll-behavior:contain;transform:translateX(-104%);visibility:hidden;transition:transform .4s var(--ease),visibility 0s .4s;box-shadow:40px 0 70px -40px rgba(0,0,0,.45)}
body.menu-open{!! $p !!} .menu{transform:none;visibility:visible;transition:transform .4s var(--ease)}
{!! $p !!} .lf-drawer-top{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem 1.25rem .9rem 1.5rem;border-bottom:1px solid var(--line)}
{!! $p !!} .lf-drawer-top .brand{flex:1 1 auto}
{!! $p !!} .lf-x{display:grid;place-items:center;flex:none;width:3rem;height:3rem;border:0;border-radius:50%;background:#fff;color:var(--text);box-shadow:0 8px 22px -6px rgba(0,0,0,.25);cursor:pointer;transition:transform .2s}
{!! $p !!} .lf-x:hover{transform:rotate(90deg)}
{!! $p !!} .lf-x svg{width:1.2rem;height:1.2rem}
{!! $p !!} .menu .menu-list{padding:.4rem 1.5rem 1rem}
{!! $p !!} .menu .menu-item>a:not(.btn){display:block;padding:1.05rem 0;border-bottom:1px solid var(--line);font:500 1.15rem/1.3 var(--body-font);color:#5d5d66;white-space:normal}
{!! $p !!} .menu .menu-item>a:not(.btn):hover,{!! $p !!} .menu .menu-item>a[aria-current]{color:color-mix(in srgb,var(--brand2-t) 70%,#000);text-decoration:none}
{!! $p !!} .menu .menu-sub{margin:.1rem 0 .5rem;padding:0 0 0 1rem;border-left:3px solid var(--brand2)}
{!! $p !!} .menu .menu-item>.menu-sub a{display:block;padding:.8rem 0 .8rem .5rem;border-bottom:1px solid var(--line);font:400 1.02rem/1.35 var(--body-font);color:#5d5d66}
{!! $p !!} .menu .menu-item>.menu-sub a:hover,{!! $p !!} .menu .menu-item>.menu-sub a[aria-current]{color:var(--brand2-t)}
{!! $p !!} .menu .menu-signin{font:400 1.05rem/1.2 var(--body-font)!important}
{!! $p !!} .lf-apply{margin-top:1.2rem}
{!! $p !!} .menu .lf-apply .btn{display:flex;width:100%;margin:0;min-height:3.4rem;border:0;background:var(--fill);color:var(--fill-ink);font-size:1.02rem}
{!! $p !!} .lf-drawer-foot{display:grid;grid-template-columns:minmax(0,1fr);gap:.4rem;margin-top:auto;padding:1.3rem 1.5rem 1.8rem;border-top:1px solid var(--line);background:var(--soft);font-size:.98rem}
{!! $p !!} .menu .lf-drawer-foot>a{display:block;padding:.2rem 0;border:0;font:400 .98rem/1.5 var(--body-font);color:var(--text);text-decoration:none;overflow-wrap:anywhere}
{!! $p !!} .menu .lf-drawer-foot .social li a{display:grid;place-items:center;width:2.75rem;height:2.75rem;padding:0;border:0;border-radius:50%;font-size:1rem;background:var(--brand);color:var(--brand-ink)}
{!! $p !!} .lf-drawer-foot .social{margin:.6rem 0 0}
{!! $p !!} .lf-burger{display:inline-flex}
{!! $p !!} .lf-scrim{display:block}
{!! $p !!} .menu .menu-list{display:block}
{!! $p !!} .menu .menu-item{display:block;position:static}
{!! $p !!} .menu .lf-apply{margin-left:0}
{!! $p !!} .menu .menu-item>a:not(.btn){border-radius:0;opacity:1;text-transform:none;background:none;letter-spacing:0}
{!! $p !!} .menu .menu-sub{display:block;position:static;min-width:0;border-radius:0;background:none;box-shadow:none}
{!! $p !!} .menu .menu-item>.menu-sub a{text-transform:none;letter-spacing:0;white-space:normal}
{!! $p !!} .menu .menu-item>.menu-sub a:hover{background:none}
