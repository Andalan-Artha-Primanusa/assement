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
    <body class="font-sans antialiased text-gray-900">
        <div class="min-h-screen bg-white lg:grid lg:grid-cols-[minmax(420px,46%)_1fr]">
            <main class="flex items-center min-h-screen px-6 py-10 sm:px-10 lg:px-16 xl:px-20">
                <div class="w-full max-w-md">


                    <div class="mt-10">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <aside class="relative hidden min-h-screen overflow-hidden bg-gray-50 lg:flex lg:items-center lg:justify-center">
                <div class="mx-auto w-full max-w-2xl px-12">
                    <img src="{{ asset('images/competra-logo.png') }}" alt="Competra" class="h-auto w-full">
                </div>
            </aside>
        </div>

        @include('partials.app-popups')
    </body>
</html>
