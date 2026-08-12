<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="csrf-refresh-url" content="{{ route('csrf-token') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('partials.favicon')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-screen overflow-hidden font-sans antialiased text-gray-900">
        <div class="h-screen overflow-hidden bg-white lg:grid lg:grid-cols-[minmax(420px,46%)_1fr]">
            <main class="flex h-screen items-center overflow-hidden px-6 py-6 sm:px-10 lg:px-16 xl:px-20">
                <div class="w-full max-w-md">


                    <div>
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <aside class="relative hidden h-screen overflow-hidden bg-gray-50 lg:block">
                <img src="{{ asset('images/login-mechanic.png') }}" alt="Assessment" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-br from-gray-950/25 via-transparent to-gray-950/45"></div>
            </aside>
        </div>

        @include('partials.app-popups')
    </body>
</html>
