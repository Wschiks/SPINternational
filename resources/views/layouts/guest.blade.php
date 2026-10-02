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
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-[#09091c]">
        <x-site-menu />
        <div class="min-h-screen flex flex-col justify-center items-center gap-6 bg-[radial-gradient(circle_at_50%_20%,#302050_0%,#101027_50%,#09091c_100%)] px-4 pb-10 pt-24">
            <a href="{{ route('home') }}" aria-label="SPINternational startpagina">
                <img src="{{ asset('images/game/logo.png') }}" alt="SPINternational" class="w-48 drop-shadow-[0_0_20px_rgba(236,72,153,0.4)]">
            </a>
            <div class="w-full sm:max-w-md rounded-2xl border border-indigo-300/50 bg-white px-6 py-6 shadow-[0_22px_60px_rgba(0,0,0,0.5),0_0_24px_rgba(99,102,241,0.2)] sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
