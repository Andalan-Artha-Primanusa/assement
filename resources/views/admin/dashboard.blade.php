<x-app-layout>
    @php
        $selectedTypeLabel = \App\Models\QuestionPackage::typeLabel($selectedType ?? null);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">
                    @if ($selectedType ?? null)
                        Dashboard {{ $selectedTypeLabel }}
                    @else
                        {{ __('Dashboard Admin') }}
                    @endif
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    @if ($selectedType ?? null)
                        Ringkasan screening {{ $selectedTypeLabel }} saja.
                    @else
                        Ringkasan screening, performa peserta, dan keamanan assessment.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.packages.create') }}" class="inline-flex min-h-[44px] items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">Tambah Paket</a>
                <a href="{{ route('admin.questions.create') }}" class="inline-flex min-h-[44px] items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah Soal</a>
                <a href="{{ route('admin.users.create') }}" class="inline-flex min-h-[44px] items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">Tambah User</a>
            </div>
        </div>
    </x-slot>

    @php
        $totalStatus = ($chartData['submitted'] ?? 0) + ($chartData['blocked'] ?? 0) + ($chartData['pending'] ?? 0) + ($chartData['notStarted'] ?? 0);
        $completionRate = $totalStatus > 0 ? round((($chartData['submitted'] ?? 0) / $totalStatus) * 100) : 0;
        $dailyTotal = array_sum($chartData['dailyTotals'] ?? []);
        $maxBucket = max($scoreBuckets ?: [0]) ?: 1;
        $scoreColors = [
            '0-59' => 'bg-rose-500',
            '60-69' => 'bg-orange-500',
            '70-79' => 'bg-amber-500',
            '80-89' => 'bg-emerald-500',
            '90-100' => 'bg-indigo-500',
        ];
        $metricCards = [
            ['label' => 'Total Soal', 'value' => $stats['questions'], 'helper' => $stats['active_questions'].' aktif', 'accent' => 'bg-gray-900'],
            ['label' => 'Paket Soal', 'value' => $stats['packages'], 'helper' => 'bank soal terpisah', 'accent' => 'bg-sky-600'],
            ['label' => 'Peserta', 'value' => $stats['users'], 'helper' => 'akun non-admin', 'accent' => 'bg-indigo-600'],
            ['label' => 'Assessment Selesai', 'value' => $stats['assessments'], 'helper' => $dailyTotal.' selesai dalam 30 hari', 'accent' => 'bg-emerald-600'],
            ['label' => 'Belum Test', 'value' => $stats['not_started'] ?? 0, 'helper' => 'peserta belum mulai', 'accent' => 'bg-slate-500'],
            ['label' => 'Terblokir', 'value' => $stats['blocked_assessments'], 'helper' => 'butuh review admin', 'accent' => 'bg-rose-600'],
            ['label' => 'Menunggu Review', 'value' => $stats['pending_review'] ?? 0, 'helper' => 'khusus SHE essay/upload', 'accent' => 'bg-amber-600'],
            ['label' => 'Rata-rata Nilai', 'value' => number_format($stats['average_score'], 1), 'helper' => 'dari assessment selesai', 'accent' => 'bg-violet-600'],
            ['label' => 'Completion', 'value' => $completionRate.'%', 'helper' => 'status selesai', 'accent' => 'bg-cyan-600'],
        ];
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($metricCards as $metric)
                    <div class="min-h-[150px] rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $metric['label'] }}</p>
                                <p class="mt-2 text-3xl font-semibold text-gray-950">{{ $metric['value'] }}</p>
                            </div>
                            <span class="mt-1 h-3 w-3 shrink-0 rounded-full {{ $metric['accent'] }}"></span>
                        </div>
                        <p class="mt-3 text-sm leading-5 text-gray-500">{{ $metric['helper'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(320px,0.9fr)]">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-950">Tren Assessment 30 Hari</h3>
                            <p class="mt-1 text-sm text-gray-500">Jumlah assessment yang selesai per hari.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $dailyTotal }} selesai</span>
                    </div>
                    <div class="mt-6 h-[330px]">
                        <canvas id="lineChart"></canvas>
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">Status Assessment</h3>
                        <p class="mt-1 text-sm text-gray-500">Selesai, terblokir, berjalan, dan belum mulai.</p>
                    </div>
                    <div class="mt-5 h-[250px]">
                        <canvas id="donutChart"></canvas>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3 text-center text-xs sm:grid-cols-4">
                        <div>
                            <p class="font-semibold text-emerald-700">{{ $chartData['submitted'] ?? 0 }}</p>
                            <p class="mt-1 text-gray-500">Selesai</p>
                        </div>
                        <div>
                            <p class="font-semibold text-rose-700">{{ $chartData['blocked'] ?? 0 }}</p>
                            <p class="mt-1 text-gray-500">Terblokir</p>
                        </div>
                        <div>
                            <p class="font-semibold text-amber-700">{{ $chartData['pending'] ?? 0 }}</p>
                            <p class="mt-1 text-gray-500">Berjalan</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700">{{ $chartData['notStarted'] ?? 0 }}</p>
                            <p class="mt-1 text-gray-500">Belum Test</p>
                        </div>
                    </div>
                </section>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">Rata-rata Nilai per Paket</h3>
                        <p class="mt-1 text-sm text-gray-500">Perbandingan performa tiap paket soal.</p>
                    </div>
                    <div class="mt-6 h-[330px]">
                        <canvas id="packageChart"></canvas>
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">Kategori Soal</h3>
                        <p class="mt-1 text-sm text-gray-500">Jumlah soal aktif berdasarkan kategori.</p>
                    </div>
                    <div class="mt-6 h-[330px]">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </section>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">Distribusi Nilai</h3>
                        <p class="mt-1 text-sm text-gray-500">Sebaran nilai akhir peserta.</p>
                    </div>
                    <div class="mt-6 space-y-5">
                        @foreach ($scoreBuckets as $label => $total)
                            <div>
                                <div class="mb-2 flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $scoreColors[$label] ?? 'bg-gray-500' }}"></span>
                                        <span class="font-medium text-gray-700">{{ $label }}</span>
                                    </div>
                                    <span class="text-gray-500">{{ $total }} peserta</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full {{ $scoreColors[$label] ?? 'bg-gray-500' }}" style="width: {{ ($total / $maxBucket) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-950">Assessment Terblokir</h3>
                            <p class="mt-1 text-sm text-gray-500">Peserta yang perlu dibuka aksesnya oleh admin.</p>
                        </div>
                        <a href="{{ route('admin.assessments.index', ['status' => 'blocked']) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                    <th class="py-3 pr-4">Peserta</th>
                                    <th class="px-4 py-3">Waktu</th>
                                    <th class="px-4 py-3">Pelanggaran</th>
                                    <th class="px-4 py-3">Alasan</th>
                                    <th class="py-3 pl-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($blockedAssessments as $assessment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-3 pr-4 font-medium text-gray-900">{{ $assessment->user->name }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $assessment->blocked_at?->format('d M Y H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                {{ $assessment->security_violations }}/{{ config('assessment.max_security_blocks') }}
                                            </span>
                                        </td>
                                        <td class="max-w-[220px] truncate px-4 py-3 text-gray-700">{{ $assessment->block_reason }}</td>
                                        <td class="py-3 pl-4 text-right">
                                            <form method="POST" action="{{ route('admin.assessments.unblock', $assessment) }}" data-confirm
                                                  data-confirm-title="Buka akses peserta?"
                                                  data-confirm-message="Peserta {{ $assessment->user->name }} bisa melanjutkan assessment setelah akses dibuka."
                                                  data-confirm-text="Ya, buka akses">
                                                @csrf
                                                <button class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                                    Buka Akses
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-gray-500">Tidak ada assessment yang terblokir.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <section class="mt-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950">Hasil Terbaru</h3>
                        <p class="mt-1 text-sm text-gray-500">Assessment terakhir yang sudah selesai.</p>
                    </div>
                    <a href="{{ route('admin.assessments.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="py-3 pr-4">Peserta</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Benar</th>
                                <th class="px-4 py-3">Nilai</th>
                                <th class="py-3 pl-4 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($latestAssessments as $assessment)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $assessment->user->name }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $assessment->submitted_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $assessment->correct_answers }}/{{ $assessment->total_questions }}</td>
                                    <td class="px-4 py-3">
                                        @if ($assessment->isPendingReview())
                                            <span class="font-semibold text-amber-700">Menunggu Review</span>
                                        @else
                                            <span class="font-semibold {{ $assessment->score >= 80 ? 'text-emerald-700' : ($assessment->score >= 60 ? 'text-amber-700' : 'text-rose-700') }}">
                                                {{ number_format($assessment->score, 2) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <a href="{{ route('assessment.result', $assessment) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Lihat</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">Belum ada assessment selesai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                initAdminCharts(@json($chartData));
            });
        </script>
    @endpush
</x-app-layout>
