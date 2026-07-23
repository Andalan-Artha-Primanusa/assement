<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assessment Terkunci') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-8 text-center shadow-sm sm:rounded-lg">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-2xl font-bold text-rose-700">
                    !
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-900">Akses assessment diblokir</h3>
                <p class="mt-3 text-sm text-gray-600">
                    Sistem mendeteksi aktivitas di luar halaman assessment. Admin perlu membuka akses agar Anda bisa melanjutkan.
                </p>

                <div class="mt-5 rounded-md bg-gray-50 px-4 py-3 text-left text-sm text-gray-700">
                    <p><span class="font-semibold">Waktu diblokir:</span> {{ $assessment->blocked_at?->format('d M Y H:i') }}</p>
                    <p class="mt-1"><span class="font-semibold">Alasan:</span> {{ $assessment->block_reason }}</p>
                    <p class="mt-1">
                        <span class="font-semibold">Pelanggaran:</span>
                        <span class="{{ $assessment->security_violations >= config('assessment.max_security_blocks', 2) ? 'font-bold text-rose-600' : '' }}">
                            {{ $assessment->security_violations }}/{{ config('assessment.max_security_blocks') }}
                        </span>
                        @if ($assessment->security_violations >= config('assessment.max_security_blocks', 2))
                            <span class="ml-1 text-xs text-rose-600">(Maksimal tercapai)</span>
                        @endif
                    </p>
                    @if ($assessment->ends_at)
                        <p class="mt-1"><span class="font-semibold">Sisa waktu:</span> {{ $assessment->ends_at->format('d M Y H:i') }}</p>
                    @endif
                </div>

                @if ($assessment->security_violations >= config('assessment.max_security_blocks', 2))
                    <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 text-left">
                        <p class="font-semibold">Pelanggaran sudah mencapai batas maksimal.</p>
                        <p class="mt-1">Admin harus membuka akses secara manual agar Anda bisa melanjutkan assessment. Hubungi admin jika Anda merasa ini adalah kesalahan.</p>
                    </div>
                @endif

                <div class="mt-5">
                    <p id="auto-refresh-text" class="text-xs text-gray-400 mb-3">Halaman ini otomatis refresh setiap 10 detik.</p>
                    <a href="{{ route('assessment.show', $assessment) }}" class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Cek Status Sekarang
                    </a>
                </div>

                <div id="unlocked-banner" class="mt-4 hidden rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 font-semibold">
                    Akses sudah dibuka! <a href="{{ route('assessment.show', $assessment) }}" class="underline">Klik di sini untuk melanjutkan.</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        setInterval(function () {
            fetch('{{ route('assessment.show', $assessment) }}', { redirect: 'follow' })
                .then(function (response) {
                    if (response.redirected) {
                        window.location.href = response.url;
                        return;
                    }
                    return response.text();
                })
                .then(function (html) {
                    if (!html) return;
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var blocked = doc.querySelector('.mx-auto.flex.h-14');
                    if (!blocked) {
                        document.getElementById('unlocked-banner').classList.remove('hidden');
                        document.getElementById('auto-refresh-text').textContent = 'Akses sudah dibuka! Mengalihkan...';
                        setTimeout(function () { window.location.reload(); }, 1000);
                    }
                })
                .catch(function () {});
        }, 10000);
    </script>
</x-app-layout>
