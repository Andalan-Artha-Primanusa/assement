<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">Kategori Invite</h2>
                <p class="mt-1 text-sm text-gray-500">Master pilihan custom untuk tracking invite peserta Mekanik dan Operator.</p>
            </div>
            <a href="{{ route('admin.operator-categories.create') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah Kategori</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Kategori</th>
                                <th class="px-6 py-3 text-center">Peserta</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($categories as $category)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-950">{{ $category->name }}</p>
                                        @if ($category->description)
                                            <p class="mt-1 max-w-xl truncate text-xs text-gray-500">{{ $category->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-gray-800">{{ $category->users_count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.operator-categories.edit', $category) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <form method="POST" action="{{ route('admin.operator-categories.destroy', $category) }}" class="inline" data-confirm
                                                  data-confirm-title="Hapus kategori {{ $category->name }}?"
                                                  data-confirm-message="Kategori hanya bisa dihapus jika belum dipakai peserta."
                                                  data-confirm-text="Ya, hapus kategori"
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
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <p class="font-semibold text-gray-700">Belum ada kategori Operator.</p>
                                        <p class="mt-1 text-sm text-gray-500">Mulai dari tombol Tambah Kategori.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
