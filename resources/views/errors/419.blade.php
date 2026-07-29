<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-10">
        <div class="w-full max-w-md rounded-lg border border-amber-200 bg-white p-6 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-xl font-bold text-amber-700">!</div>
            <h1 class="mt-4 text-2xl font-semibold text-gray-950">Sesi halaman diperbarui</h1>
            <p class="mt-2 text-sm leading-6 text-gray-600">Halaman terlalu lama terbuka atau token form sudah berubah. Login lagi atau refresh halaman sebelum mengirim data.</p>
            <a href="{{ route('login') }}" class="mt-6 inline-flex min-h-[44px] items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Kembali ke Login</a>
        </div>
    </div>

    @include('partials.app-popups')
</body>
</html>
