<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Review SHE Assessment') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-[1fr_auto]">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari peserta" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <div class="flex gap-2">
                    <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
                    <a href="{{ route('admin.she-review.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
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
                                <th class="px-4 sm:px-6 py-3 text-center">Soal MC</th>
                                <th class="px-4 sm:px-6 py-3 text-center">Essay/Upload</th>
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
                                        {{ $assessment->answers->where('question.type', 'multiple_choice')->count() }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-center text-gray-700">
                                        {{ $assessment->answers->whereIn('question.type', ['essay', 'upload'])->count() }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-center whitespace-nowrap">
                                        @if ($assessment->isPendingReview())
                                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">Perlu Review</span>
                                        @elseif ($assessment->isGraded())
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                                        @endif
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.she-review.show', $assessment) }}" class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Review</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 sm:px-6 py-10 text-center text-gray-500">Belum ada assessment yang perlu di-review.</td>
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
