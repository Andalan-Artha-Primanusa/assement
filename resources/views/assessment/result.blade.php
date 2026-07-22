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
            @endphp

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
                            {{ ucfirst($package->type) }}{{ $package->level ? ' - '.$package->level : '' }}
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

            @if ($grade)
                <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg text-center">
                    <p class="text-sm text-gray-500">Hasil Keputusan</p>
                    <p class="mt-2 text-4xl font-bold
                        {{ $grade === 'Lolos' ? 'text-emerald-600' : ($grade === 'Dipertimbangkan' ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ $grade }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
