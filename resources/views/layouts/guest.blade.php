<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <x-translations />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-[#09091c]">
        <x-site-menu />
        <div class="relative min-h-screen flex flex-col justify-center items-center gap-6 overflow-hidden bg-[radial-gradient(circle_at_50%_20%,#302050_0%,#101027_50%,#09091c_100%)] px-4 pb-10 pt-24">
            <div class="pointer-events-none absolute inset-0 opacity-20 [background-image:linear-gradient(rgba(103,232,249,0.15)_1px,transparent_1px),linear-gradient(90deg,rgba(103,232,249,0.15)_1px,transparent_1px)] [background-size:48px_48px]" aria-hidden="true"></div>
            <a href="{{ route('home') }}" class="relative" aria-label="SPINternational startpagina">
                <img src="{{ asset('images/game/logo.png') }}" alt="SPINternational" class="w-48">
            </a>
            <div class="relative w-full sm:max-w-md rounded-2xl border px-6 py-6 shadow-[0_22px_60px_rgba(0,0,0,0.5)] sm:px-8 {{ $attributes->has('dark') ? 'border-white/10 bg-[#14142d] text-white' : 'border-indigo-300/50 bg-white' }}">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
