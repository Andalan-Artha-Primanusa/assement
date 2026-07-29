<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses ditolak</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased min-h-screen bg-gray-100 flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center">
        <p class="text-7xl font-bold text-rose-600">403</p>
        <h1 class="mt-4 text-2xl font-semibold text-gray-900">Akses ditolak</h1>
        <p class="mt-2 text-gray-600">Kamu gak punya akses ke halaman ini.</p>
        <a href="{{ url('/dashboard') }}" class="mt-6 inline-flex rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Kembali ke Dashboard</a>
    </div>
</body>
</html>
