<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('CMS Admin') }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.packages.create') }}" class="rounded-md bg-emerald-600 px-4 py-3 sm:py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 min-h-[44px] sm:min-h-0 inline-flex items-center">Tambah Paket</a>
                <a href="{{ route('admin.questions.create') }}" class="rounded-md bg-indigo-600 px-4 py-3 sm:py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 min-h-[44px] sm:min-h-0 inline-flex items-center">Tambah Soal</a>
                <a href="{{ route('admin.users.create') }}" class="rounded-md bg-gray-900 px-4 py-3 sm:py-2 text-sm font-semibold text-white shadow-sm hover:bg-black min-h-[44px] sm:min-h-0 inline-flex items-center">Tambah User</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Stat Cards --}}
            <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-7">
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100">
                            <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Soal</p>
                            <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $stats['questions'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Soal Aktif</p>
                            <p class="text-2xl font-bold text-emerald-700 mt-0.5">{{ $stats['active_questions'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100">
                            <svg class="h-5 w-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</p>
                            <p class="text-2xl font-bold text-sky-700 mt-0.5">{{ $stats['packages'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Peserta</p>
                            <p class="text-2xl font-bold text-indigo-700 mt-0.5">{{ $stats['users'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100">
                            <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</p>
                            <p class="text-2xl font-bold text-cyan-700 mt-0.5">{{ $stats['assessments'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100">
                            <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Terblokir</p>
                            <p class="text-2xl font-bold text-rose-700 mt-0.5">{{ $stats['blocked_assessments'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100">
                            <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Rata-rata</p>
                            <p class="text-2xl font-bold text-violet-700 mt-0.5">{{ number_format($stats['average_score'], 1) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts Row 1: Donut + Line --}}
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Status Assessment</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Distribusi status assessment peserta</p>
                        </div>
                    </div>
                    <div class="mt-2" style="position: relative; height: 280px;">
                        <canvas id="donutChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Aktivitas 30 Hari</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Jumlah assessment selesai per hari</p>
                        </div>
                        <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">{{ array_sum($chartData['dailyTotals']) }} total</span>
                    </div>
                    <div class="mt-2" style="position: relative; height: 280px;">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Charts Row 2: Bar + Polar --}}
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Rata-rata Nilai per Paket</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Persentase nilai rata-rata per paket soal</p>
                    </div>
                    <div class="mt-2" style="position: relative; height: 280px;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Kategori Soal Terjawab</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Distribusi jumlah soal per kategori</p>
                    </div>
                    <div class="mt-2" style="position: relative; height: 280px;">
                        <canvas id="polarChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Charts Row 3: Radar + Score Distribution --}}
            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Kompetensi per Kategori</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Rata-rata nilai benar per kategori soal</p>
                    </div>
                    <div class="mt-2" style="position: relative; height: 280px;">
                        <canvas id="radarChart"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Distribusi Nilai</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Sebaran nilai assessment peserta</p>
                    </div>
                    @php($maxBucket = max($scoreBuckets ?: [0]) ?: 1)
                    <div class="mt-6 space-y-5">
                        @foreach ($scoreBuckets as $label => $total)
                            <div>
                                <div class="mb-1.5 flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full @switch(true)
                                            @case($label === '0-59') bg-red-500 @break
                                            @case($label === '60-69') bg-orange-500 @break
                                            @case($label === '70-79') bg-amber-500 @break
                                            @case($label === '80-89') bg-emerald-500 @break
                                            @default bg-indigo-500
                                        @endswitch"></span>
                                        <span class="font-medium text-gray-700">{{ $label }}</span>
                                    </div>
                                    <span class="text-gray-500 font-medium">{{ $total }} peserta</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-full rounded-full transition-all duration-500 @switch(true)
                                        @case($label === '0-59') bg-red-500 @break
                                        @case($label === '60-69') bg-orange-500 @break
                                        @case($label === '70-79') bg-amber-500 @break
                                        @case($label === '80-89') bg-emerald-500 @break
                                        @default bg-indigo-500
                                    @endswitch" style="width: {{ ($total / $maxBucket) * 100 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Blocked Assessments --}}
            <div class="mt-6 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Assessment Terblokir</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Peserta yang diblokir karena pelanggaran</p>
                    </div>
                    <a href="{{ route('admin.assessments.index', ['status' => 'blocked']) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
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
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $assessment->user->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $assessment->blocked_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-700">
                                            {{ $assessment->security_violations }}/{{ config('assessment.max_security_blocks') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 max-w-[200px] truncate">{{ $assessment->block_reason }}</td>
                                    <td class="py-3 pl-4 text-right">
                                        <form method="POST" action="{{ route('admin.assessments.unblock', $assessment) }}">
                                            @csrf
                                            <button class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition-colors">
                                                Buka Akses
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500">Tidak ada assessment yang terblokir.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Latest Results --}}
            <div class="mt-6 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Hasil Terbaru</h3>
                        <p class="text-xs text-gray-500 mt-0.5">8 assessment terakhir yang selesai</p>
                    </div>
                    <a href="{{ route('admin.assessments.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Lihat Semua</a>
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
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 pr-4 font-medium text-gray-900">{{ $assessment->user->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $assessment->submitted_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $assessment->correct_answers }}/{{ $assessment->total_questions }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold {{ $assessment->score >= 80 ? 'text-emerald-700' : ($assessment->score >= 60 ? 'text-amber-700' : 'text-rose-700') }}">
                                            {{ number_format($assessment->score, 2) }}
                                        </span>
                                    </td>
                                    <td class="py-3 pl-4 text-right">
                                        <a href="{{ route('assessment.result', $assessment) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Lihat</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500">Belum ada assessment selesai.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

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
