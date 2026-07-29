<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Assessment') }}
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if ($accessExpired)
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-5">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 mt-0.5">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-red-800">Akses Assessment Telah Berakhir</h3>
                            <p class="mt-1 text-sm text-red-700">
                                Akun Anda tidak dapat digunakan untuk mengikuti assessment karena masa akses telah habis.
                                Silakan hubungi admin untuk memperpanjang akses.
                            </p>
                            @if (Auth::user()->assessment_access_expires_at)
                                <p class="mt-2 text-xs font-semibold text-red-600">Berkhir: {{ Auth::user()->assessment_access_expires_at->format('d M Y H:i') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

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
                                @if (Auth::user()->assessment_access_expires_at)
                                    @php
                                        $daysLeft = max(0, (int) Auth::user()->assessment_access_expires_at->diffInDays(now(), false));
                                    @endphp
                                    <span class="rounded-full {{ $daysLeft > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }} px-3 py-1">
                                        Akses: {{ $daysLeft > 0 ? 'Sisa '.$daysLeft.' hari' : 'Telah berakhir' }} ({{ Auth::user()->assessment_access_expires_at->format('d M Y') }})
                                    </span>
                                @else
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                                        Akses: Tanpa batas
                                    </span>
                                @endif
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-indigo-700">
                                    Durasi: {{ round(Auth::user()->assessmentDurationMinutes() / 60, 2) }} jam
                                </span>
                                @php
                                    $displaySegmentConfig = Auth::user()->segment_config;
                                    if (empty($displaySegmentConfig) && ($assignedPackage?->type === 'she' || $assignedPackage?->has_segments)) {
                                        $displaySegmentConfig = config('assessment.she_default_segments', []);
                                    }
                                @endphp
                                @if (($assignedPackage?->has_segments || $assignedPackage?->type === 'she') && ! empty($displaySegmentConfig))
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">
                                        Bersegment: {{ count($displaySegmentConfig) }} segmen
                                        ({{ implode(', ', array_map(fn ($s) => ($s['type'] === 'multiple_choice' ? 'PG' : ($s['type'] === 'upload' ? 'Portfolio' : ucfirst($s['type']))).': '.$s['duration'].'m', $displaySegmentConfig)) }})
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0">
                            @if ($accessExpired)
                                <span class="inline-flex items-center rounded-md bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500 cursor-not-allowed">
                                    Akses Berakhir
                                </span>
                            @elseif ($openAssessment)
                                <a href="{{ route('assessment.show', $openAssessment) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                                    Lanjutkan
                                </a>
                            @else
                                <form method="POST" action="{{ route('assessment.start') }}" data-confirm
                                      data-confirm-title="Mulai assessment sekarang?"
                                      data-confirm-message="Timer akan berjalan setelah assessment dimulai. Pastikan kamu sudah siap."
                                      data-confirm-text="Ya, mulai">
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
