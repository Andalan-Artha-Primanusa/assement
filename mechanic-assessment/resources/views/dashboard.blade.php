<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Assessment') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">Bank soal aktif</p>
                    <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-4xl font-semibold text-gray-900">{{ $activeQuestionCount }}</p>
                            <p class="mt-2 text-sm text-gray-600">
                                Sistem akan memilih maksimal {{ config('assessment.question_limit') }} soal secara acak saat assessment dimulai.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="rounded-full bg-sky-50 px-3 py-1 text-sky-700">
                                    Paket: {{ $assignedPackage?->name ?? 'Semua paket aktif' }}
                                </span>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                                    Akses sampai: {{ Auth::user()->assessment_access_expires_at?->format('d M Y H:i') ?? 'tanpa batas' }}
                                </span>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700">
                                    Durasi: {{ round(Auth::user()->assessmentDurationMinutes() / 60, 2) }} jam
                                </span>
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if ($openAssessment)
                                <a href="{{ route('assessment.show', $openAssessment) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                    Lanjutkan
                                </a>
                            @else
                                <form method="POST" action="{{ route('assessment.start') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 min-h-[44px]">
                                        Mulai Assessment
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-sm font-medium text-gray-500">Status terakhir</p>
                        @if ($assessments->isNotEmpty())
                            @php($latest = $assessments->first())
                            <p class="mt-2 text-4xl font-semibold text-gray-900">{{ number_format($latest->score, 0) }}</p>
                            <p class="mt-2 text-sm text-gray-600">
                                {{ $latest->correct_answers }} benar dari {{ $latest->total_questions }} soal.
                            </p>
                        @else
                            <p class="mt-2 text-2xl font-semibold text-gray-900">Belum ada hasil</p>
                            <p class="mt-2 text-sm text-gray-600">Mulai assessment pertama untuk melihat nilai.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-lg font-semibold text-gray-900">Riwayat Assessment</h3>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="py-3 pr-2 sm:pr-4">Tanggal</th>
                                    <th class="px-2 sm:px-4 py-3">Benar</th>
                                    <th class="px-2 sm:px-4 py-3">Total</th>
                                    <th class="px-2 sm:px-4 py-3">Nilai</th>
                                    <th class="py-3 pl-2 sm:pl-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($assessments as $assessment)
                                    <tr>
                                        <td class="py-3 pr-2 sm:pr-4 text-gray-700 whitespace-nowrap">{{ $assessment->submitted_at?->format('d M Y H:i') }}</td>
                                        <td class="px-2 sm:px-4 py-3 text-gray-700">{{ $assessment->correct_answers }}</td>
                                        <td class="px-2 sm:px-4 py-3 text-gray-700">{{ $assessment->total_questions }}</td>
                                        <td class="px-2 sm:px-4 py-3 font-semibold text-gray-900">{{ number_format($assessment->score, 2) }}</td>
                                        <td class="py-3 pl-2 sm:pl-4 text-right whitespace-nowrap">
                                            <a href="{{ route('assessment.result', $assessment) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-gray-500">Belum ada riwayat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $assessments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
