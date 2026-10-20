<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" suppressHydrationWarning>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        @php
            // The product name, set by the platform owner in Settings; never the framework's default "Laravel"
            $brand = \App\Models\PlatformSetting::get('platform_name') ?: (config('app.name') !== 'Laravel' ? config('app.name') : 'Adino School Systems');
        @endphp
        <title inertia>{{ $brand }}</title>
        @php
            $platformFavicon = \App\Models\PlatformSetting::get('platform_favicon');
            $faviconHref = $platformFavicon ? asset('storage/' . $platformFavicon) : asset('favicon.ico');
        @endphp
        <link id="app-favicon" rel="icon" type="image/{{ $platformFavicon ? 'x-icon' : 'svg+xml' }}" href="{{ $platformFavicon ? $faviconHref : asset('favicon.svg') }}" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&display=swap" />
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
