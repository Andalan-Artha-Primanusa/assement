<x-app-layout>
    @php
        $selectedTypeLabel = \App\Models\QuestionPackage::typeLabel($selectedType);
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            @if ($selectedType === 'she')
                {{ __('Review SHE Assessment') }}
            @else
                Review Assessment {{ $selectedTypeLabel }}
            @endif
        </h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-[1fr_auto]">
                <input type="hidden" name="type" value="{{ $selectedType }}">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari peserta" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <div class="flex gap-2">
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
                    <a href="{{ route('admin.she-review.index', ['type' => $selectedType]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
                </div>
            </form>

            <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-4 sm:px-6 py-3">Peserta</th>
                                <th class="px-4 sm:px-6 py-3">Paket</th>
                                <th class="px-4 sm:px-6 py-3">Kirim Pada</th>
                                <th class="px-4 sm:px-6 py-3 text-center">Soal</th>
                                @if ($selectedType === 'she')
                                    <th class="px-4 sm:px-6 py-3 text-center">Essay/Upload</th>
                                @endif
                                <th class="px-4 sm:px-6 py-3 text-center">Status</th>
                                <th class="px-4 sm:px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($assessments as $assessment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-900 whitespace-nowrap">{{ $assessment->user->name }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700 whitespace-nowrap">{{ $assessment->questionPackage?->name ?? '-' }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-700 whitespace-nowrap">{{ $assessment->submitted_at?->format('d M Y H:i') }}</td>
                                    <td class="px-4 sm:px-6 py-4 text-center text-gray-700">
                                        {{ $assessment->total_questions }}
                                    </td>
                                    @if ($selectedType === 'she')
                                        <td class="px-4 sm:px-6 py-4 text-center text-gray-700">
                                            {{ $assessment->answers->whereIn('question.type', ['essay', 'upload'])->count() }}
                                        </td>
                                    @endif
                                    <td class="px-4 sm:px-6 py-4 text-center whitespace-nowrap">
                                        @if ($assessment->isPendingReview())
                                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Menunggu Review</span>
                                        @elseif ($assessment->isGraded())
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Selesai Direview</span>
                                        @else
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">Selesai</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.she-review.show', $assessment) }}" class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                            @if ($selectedType === 'she' && $assessment->isPendingReview())
                                                Review
                                            @else
                                                Lihat
                                            @endif
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $selectedType === 'she' ? 7 : 6 }}" class="px-4 sm:px-6 py-10 text-center text-gray-500">
                                        @if ($selectedType === 'she')
                                            Belum ada assessment yang perlu di-review.
                                        @else
                                            Belum ada assessment selesai.
                                        @endif
                                    </td>
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
