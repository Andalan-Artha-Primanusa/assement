<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assessment Terkunci') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-8 text-center shadow-sm sm:rounded-lg">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-rose-50 text-2xl font-semibold text-rose-700">
                    !
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-900">Akses assessment diblokir</h3>
                <p class="mt-3 text-sm text-gray-600">
                    Sistem mendeteksi aktivitas di luar halaman assessment. Admin masih bisa membuka akses sampai batas {{ config('assessment.max_security_blocks') }} kali.
                </p>
                <div class="mt-5 rounded-md bg-gray-50 px-4 py-3 text-left text-sm text-gray-700">
                    <p><span class="font-semibold">Waktu:</span> {{ $assessment->blocked_at?->format('d M Y H:i') }}</p>
                    <p class="mt-1"><span class="font-semibold">Alasan:</span> {{ $assessment->block_reason }}</p>
                    <p class="mt-1"><span class="font-semibold">Pelanggaran:</span> {{ $assessment->security_violations }}/{{ config('assessment.max_security_blocks') }}</p>
                </div>
                <a href="{{ route('assessment.show', $assessment) }}" class="mt-6 inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                    Cek Status
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
