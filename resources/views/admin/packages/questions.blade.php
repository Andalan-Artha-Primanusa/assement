<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Soal: {{ $package->name }}</h2>
            <div class="flex gap-2">
                <a href="{{ route('admin.questions.create', ['question_package_id' => $package->id]) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 inline-flex items-center min-h-[44px]">Tambah Soal</a>
                <a href="{{ route('admin.questions.import', ['question_package_id' => $package->id]) }}" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 inline-flex items-center min-h-[44px]">Import Excel</a>
                <a href="{{ route('admin.packages.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="GET" class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-4">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari soal" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <select name="category" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                <button class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">Filter</button>
            </form>

            <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Soal</th>
                                <th class="px-6 py-3">Kategori</th>
                                <th class="px-6 py-3">Level</th>
                                <th class="px-6 py-3 text-center">Kunci</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($questions as $question)
                                <tr class="hover:bg-gray-50">
                                    <td class="max-w-xl px-6 py-4 text-gray-900">
                                        <div class="flex items-start gap-2">
                                            @if ($question->image)
                                                <img src="{{ route('files.show', $question->image) }}" alt="" class="h-10 w-10 shrink-0 rounded object-cover">
                                            @endif
                                            <p class="line-clamp-2">{{ $question->text }}</p>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $question->category }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ ucfirst($question->difficulty) }}</td>
                                    <td class="px-6 py-4 text-center font-semibold uppercase text-gray-900">{{ $question->correct_option }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $question->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $question->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.questions.edit', $question) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" class="inline" data-confirm
                                                  data-confirm-title="Hapus soal ini?"
                                                  data-confirm-message="Soal akan dihapus jika belum pernah dipakai, atau dinonaktifkan jika sudah memiliki riwayat jawaban."
                                                  data-confirm-text="Ya, hapus soal"
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
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada soal di paket ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $questions->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
