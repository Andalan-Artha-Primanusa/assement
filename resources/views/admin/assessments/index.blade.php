<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Assessment') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-5">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari peserta" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select name="package" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua paket</option>
                    @foreach ($packages as $pkg)
                        <option value="{{ $pkg->id }}" @selected(request('package') == $pkg->id)>{{ $pkg->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua status</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Selesai</option>
                    <option value="pending_review" @selected(request('status') === 'pending_review')>Menunggu Review</option>
                    <option value="graded" @selected(request('status') === 'graded')>Selesai Direview</option>
                    <option value="pending" @selected(request('status') === 'pending')>Sedang jalan</option>
                    <option value="blocked" @selected(request('status') === 'blocked')>Terblokir</option>
                </select>
                <div class="flex gap-2 md:col-span-2">
                    <button class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
                    <a href="{{ route('admin.assessments.index') }}" class="w-full rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
                    <a href="{{ route('admin.assessments.export', request()->query()) }}" class="w-full rounded-md border border-emerald-300 bg-emerald-50 px-4 py-2 text-center text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Export CSV</a>
                </div>
            </form>

            <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-4 sm:px-6 py-3">Peserta</th>
                                <th class="px-4 sm:px-6 py-3">Paket</th>
                                <th class="px-4 sm:px-6 py-3">Mulai</th>
                                <th class="px-4 sm:px-6 py-3">Berakhir</th>
                                <th class="px-4 sm:px-6 py-3 text-center">Benar</th>
                                <th class="px-4 sm:px-6 py-3 text-center">Nilai</th>
                                <th class="px-4 sm:px-6 py-3 text-center">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($assessments as $assessment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $assessment->user->name }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700 whitespace-nowrap">{{ $assessment->questionPackage?->name ?? '-' }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700 whitespace-nowrap">{{ $assessment->started_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700 whitespace-nowrap text-xs">
                                        @if ($assessment->ends_at)
                                            {{ $assessment->ends_at->format('d M Y H:i') }}
                                            @if (!$assessment->isSubmitted() && $assessment->ends_at->isFuture())
                                                <span class="text-amber-600">({{ now()->diffInMinutes($assessment->ends_at, true) }}m)</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-center text-gray-700">{{ $assessment->correct_answers }}/{{ $assessment->total_questions }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-center font-semibold text-gray-900">{{ $assessment->score ? number_format($assessment->score, 2) : '-' }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-center whitespace-nowrap">
                                        @if ($assessment->isGraded())
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Selesai Direview</span>
                                        @elseif ($assessment->isPendingReview())
                                            <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Menunggu Review</span>
                                        @elseif ($assessment->isSubmitted())
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                                        @elseif ($assessment->isBlocked())
                                            <span class="rounded-full bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">Terblokir</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Berjalan</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            @if ($assessment->isSubmitted())
                                                <a href="{{ route('assessment.result', $assessment) }}" class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Detail</a>
                                                <a href="{{ route('admin.assessments.pdf', $assessment) }}" class="rounded-md bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">PDF</a>
                                            @elseif ($assessment->isBlocked())
                                                <form method="POST" action="{{ route('admin.assessments.unblock', $assessment) }}" class="inline">
                                                    @csrf
                                                    <button class="rounded-md bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Buka</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.assessments.extend', $assessment) }}" class="inline-flex items-center gap-1">
                                                    @csrf
                                                    <input type="number" name="extra_minutes" value="15" min="1" max="1440" class="w-14 rounded border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <button class="rounded-md bg-amber-50 px-2 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">+Waktu</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 sm:px-6 py-10 text-center text-gray-500">Belum ada assessment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-4 sm:px-6 py-4">
                    {{ $assessments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
