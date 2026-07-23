<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Hasil Assessment') }}</h2>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali ke dashboard</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($assessment->auto_submitted_at)
                <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Assessment otomatis dianggap selesai pada {{ $assessment->auto_submitted_at->format('d M Y H:i') }} karena pelanggaran melebihi batas.
                </div>
            @endif

            @if ($assessment->isPendingReview())
                <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Assessment sedang menunggu review oleh admin untuk soal Essay/Upload. Nilai akhir akan ditampilkan setelah review selesai.
                </div>
            @endif

            @php
                $package = $assessment->questionPackage;
                $grade = $package ? $package->getGrade((float) $assessment->score) : null;
                $showCertificate = $package && $package->is_certificate && $grade && in_array($grade, ['Lolos', 'Dipertimbangkan']);
            @endphp

            {{-- Info Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Peserta</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">{{ $assessment->user->name }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Paket soal</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">
                        {{ $package?->name ?? 'Semua paket' }}
                        @if ($package?->level)
                            <span class="ml-1 text-sm font-medium text-purple-600">({{ $package->level }})</span>
                        @endif
                    </p>
                    @if ($package)
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $package->type === 'she' ? 'SHE' : ucfirst($package->type) }}{{ $package->level ? ' - '.$package->level : '' }}
                            | Threshold: >= {{ $package->min_score_pertimbangan ?? '-' }} / {{ $package->min_score_lolos ?? '-' }}
                        </p>
                    @endif
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Jawaban benar</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ $assessment->correct_answers }}/{{ $assessment->total_questions }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Nilai</p>
                    <p class="mt-2 text-3xl font-semibold text-indigo-700">{{ number_format($assessment->score, 2) }}</p>
                </div>
            </div>

            {{-- Grade Result --}}
            @if ($grade)
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @if ($grade === 'Lolos')
                        <div class="bg-gradient-to-r from-emerald-500 to-green-500 p-8 text-center text-white">
                            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold">Lolos</h3>
                            <p class="mt-2 text-sm text-emerald-100">Selamat! Anda dinyatakan <strong>LOLOS</strong> dalam assessment ini.</p>
                        </div>
                    @elseif ($grade === 'Dipertimbangkan')
                        <div class="bg-gradient-to-r from-amber-500 to-yellow-500 p-8 text-center text-white">
                            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold">Dipertimbangkan</h3>
                            <p class="mt-2 text-sm text-amber-100">Hasil Anda akan ditinjau lebih lanjut oleh tim HR.</p>
                        </div>
                    @else
                        <div class="bg-gradient-to-r from-rose-500 to-red-500 p-8 text-center text-white">
                            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l2-2m-2 2l-2-2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold">Tidak Lolos</h3>
                            <p class="mt-2 text-sm text-rose-100">Nilai Anda belum mencapai ambang kelulusan. Silakan hubungi admin untuk informasi lebih lanjut.</p>
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="grid gap-4 sm:grid-cols-3 text-center">
                            <div>
                                <p class="text-xs text-gray-500">Ambang Dipertimbangkan</p>
                                <p class="mt-1 text-lg font-bold text-amber-600">{{ $package->min_score_pertimbangan ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Nilai Anda</p>
                                <p class="mt-1 text-lg font-bold text-indigo-600">{{ number_format($assessment->score, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Ambang Lolos</p>
                                <p class="mt-1 text-lg font-bold text-emerald-600">{{ $package->min_score_lolos ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Certificate --}}
            @if ($showCertificate)
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-200">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Selamat! Anda Berhasil</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda berhak mendapatkan sertifikat kelulusan assessment.</p>
                        <a href="{{ route('assessment.certificate', $assessment) }}" target="_blank"
                           class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-200 transition-all hover:from-indigo-700 hover:to-blue-700 hover:shadow-lg hover:-translate-y-0.5">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Lihat Sertifikat
                        </a>
                    </div>
                </div>
            @endif

            {{-- Segment Progress --}}
            @if ($assessment->segments()->count() > 0)
                <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Progress Segment</p>
                    <div class="flex gap-3">
                        @foreach ($assessment->segments as $seg)
                            <div class="flex-1 rounded-lg border p-3 text-center
                                {{ $seg->isCompleted() ? 'border-emerald-200 bg-emerald-50' : ($seg->isInProgress() ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50') }}">
                                <p class="text-xs font-semibold {{ $seg->isCompleted() ? 'text-emerald-600' : ($seg->isInProgress() ? 'text-indigo-600' : 'text-gray-400') }}">
                                    {{ $seg->type === 'multiple_choice' ? 'PG' : ucfirst($seg->type) }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">{{ $seg->duration_minutes }} menit</p>
                                @if ($seg->completed_at)
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $seg->completed_at->format('H:i') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Back to Dashboard Button --}}
            <div class="mt-8 text-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-8 py-3 text-sm font-semibold text-white shadow-sm hover:bg-black transition-all">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
