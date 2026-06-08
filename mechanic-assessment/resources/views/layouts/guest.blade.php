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
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen bg-white lg:grid lg:grid-cols-[minmax(420px,46%)_1fr]">
            <main class="flex min-h-screen items-center px-6 py-10 sm:px-10 lg:px-16 xl:px-20">
                <div class="w-full max-w-md">
                    <a href="/" class="inline-flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-md bg-gray-900 text-sm font-semibold text-white">SM</span>
                        <span class="text-lg font-semibold text-gray-900">Screening Mechanic</span>
                    </a>

                    <div class="mt-10">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <aside class="relative hidden min-h-screen overflow-hidden lg:block">
                <img src="{{ asset('images/login-mechanic.png') }}" alt="Mechanic assessment" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-950/35 via-gray-950/10 to-gray-950/60"></div>
                <div class="absolute bottom-0 left-0 right-0 p-12 text-white">
                    <p class="max-w-xl text-3xl font-semibold leading-tight">Assessment mechanic yang tertib, fokus, dan mudah dipantau admin.</p>
                    <div class="mt-6 flex gap-3 text-sm font-medium">
                        <span class="rounded-md bg-white/15 px-3 py-2 backdrop-blur">Random soal</span>
                        <span class="rounded-md bg-white/15 px-3 py-2 backdrop-blur">CMS admin</span>
                        <span class="rounded-md bg-white/15 px-3 py-2 backdrop-blur">Secure test</span>
                    </div>
                </div>
            </aside>
        </div>
    </body>
</html>
