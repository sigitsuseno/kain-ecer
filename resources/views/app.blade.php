<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if (file_exists(public_path('manifest.webmanifest')))
        <link rel="manifest" href="/manifest.webmanifest">
    @else
        {{-- Saat dev mode, Vite PWA biasanya menyuntikkan ini otomatis --}}
    @endif
    <meta name="theme-color" content="#312e81">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <title>{{ config('app.name', 'ERP-Lite') }}</title>
    {{-- <script id="vite-plugin-pwa:register-sw" src="/registerSW.js"></script> --}}
    <!-- Fonts -->
    @vite(['resources/js/app.js'])
    @inertiaHead
    @routes
</head>

<body>
    @inertia
</body>

</html>
