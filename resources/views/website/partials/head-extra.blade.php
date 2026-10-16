@php
    $gaId = (string) $site->option('ga_id');
    $vg = (string) $site->option('verify_google');
    $vb = (string) $site->option('verify_bing');
@endphp
@if (preg_match('/^[A-Za-z0-9_-]{20,100}$/', $vg))<meta name="google-site-verification" content="{{ $vg }}">@endif
@if (preg_match('/^[A-Za-z0-9_-]{20,100}$/', $vb))<meta name="msvalidate.01" content="{{ $vb }}">@endif
@if (preg_match('/^G-[A-Z0-9]{6,14}$/', $gaId) && ! $preview)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $gaId }}');</script>
@endif
