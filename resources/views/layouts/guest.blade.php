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
    <body class="font-sans antialiased text-gray-900">
        <div class="min-h-screen bg-white lg:grid lg:grid-cols-[minmax(420px,46%)_1fr]">
            <main class="flex items-center min-h-screen px-6 py-10 sm:px-10 lg:px-16 xl:px-20">
                <div class="w-full max-w-md">


                    <div class="mt-10">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <aside class="relative hidden min-h-screen overflow-hidden lg:block">
                <img src="{{ asset('images/login-mechanic.png') }}" alt="Mechanic assessment" class="absolute inset-0 object-cover w-full h-full">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-950/35 via-gray-950/10 to-gray-950/60"></div>
                <div class="absolute bottom-0 left-0 right-0 p-12 text-white">
                    <p class="max-w-xl text-3xl font-semibold leading-tight">Assessment mechanic yang tertib, fokus, dan mudah dipantau admin.</p>
                    <div class="flex gap-3 mt-6 text-sm font-medium">
                        <span class="px-3 py-2 rounded-md bg-white/15 backdrop-blur">Random soal</span>
                        <span class="px-3 py-2 rounded-md bg-white/15 backdrop-blur">CMS admin</span>
                        <span class="px-3 py-2 rounded-md bg-white/15 backdrop-blur">Secure test</span>
                    </div>
                </div>
            </aside>
        </div>
    </body>
</html>
