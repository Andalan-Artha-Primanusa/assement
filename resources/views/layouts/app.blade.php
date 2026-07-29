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

        <style>[x-cloak] { display: none !important; }</style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('styles')
    </head>
    <body class="font-sans antialiased text-gray-900">
        <div x-data="{ sidebarOpen: window.innerWidth >= 640 }" class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <!-- Mobile Overlay -->
            <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-gray-900/50 sm:hidden" style="display: none;" x-cloak></div>

            <div :class="sidebarOpen ? 'sm:pl-64' : ''" class="pt-14 sm:pt-0 transition-[padding-left] duration-300">
                <!-- Page Heading -->
                @isset($header)
                    <header class="border-b border-gray-200 bg-white/95 shadow-sm">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>

        @include('partials.app-popups')

        @stack('scripts')
    </body>
</html>
