<x-app-layout>
    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Data Penilaian Interview</h1>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.interview-assessments.export') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Export CSV
                    </a>
                    <a href="{{ route('admin.interview-assessments.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Tambah Penilaian
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-md bg-emerald-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Tanggal</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Kandidat</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Jabatan</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Site</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Template</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Skor</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-900">Rekomendasi</th>
                                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($assessments as $assessment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $assessment->interview_date?->format('d M Y') ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $assessment->candidate_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $assessment->job_title ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $assessment->location ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $assessment->template->name }}</td>
                                    <td class="px-6 py-4 text-center text-sm">
                                        <span class="font-semibold">{{ $assessment->percentage }}%</span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm">
                                        @php
                                            $recColors = [
                                                'DIREKOMENDASIKAN' => 'bg-emerald-100 text-emerald-800',
                                                'DIPERTIMBANGKAN' => 'bg-amber-100 text-amber-800',
                                                'TIDAK DIREKOMENDASIKAN' => 'bg-red-100 text-red-800',
                                            ];
                                            $color = $recColors[$assessment->recommendation] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $color }}">
                                            {{ $assessment->recommendation ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.interview-assessments.show', $assessment) }}" class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Detail</a>
                                            <a href="{{ route('admin.interview-assessments.edit', $assessment) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <a href="{{ route('admin.interview-assessments.pdf', $assessment) }}" class="rounded-md bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">PDF</a>
                                            <form method="POST" action="{{ route('admin.interview-assessments.destroy', $assessment) }}" class="inline" data-confirm
                                                  data-confirm-title="Hapus penilaian interview?"
                                                  data-confirm-message="Data penilaian {{ $assessment->candidate_name }} akan dihapus permanen."
                                                  data-confirm-text="Ya, hapus"
                                                  data-confirm-variant="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-gray-500">Belum ada data penilaian interview.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $assessments->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
