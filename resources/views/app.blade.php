<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" suppressHydrationWarning>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title inertia>{{ config('app.name', 'Genius SMS') }}</title>
        @php
            $platformFavicon = \App\Models\PlatformSetting::get('platform_favicon');
            $faviconHref = $platformFavicon ? asset('storage/' . $platformFavicon) : asset('favicon.ico');
        @endphp
        <link id="app-favicon" rel="icon" type="image/x-icon" href="{{ $faviconHref }}" />
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
