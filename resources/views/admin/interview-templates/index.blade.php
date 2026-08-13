<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">Template Interview</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola form template, kategori penilaian, dan aspek-aspek interview.</p>
            </div>
            <a href="{{ route('admin.interview-templates.create') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah Template</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-emerald-50 p-4 text-sm font-medium text-emerald-800 border border-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Nama Template</th>
                                <th class="px-6 py-3 text-center">Tipe</th>
                                <th class="px-6 py-3 text-center">Rekomendasi / Dipertimbangkan Boundary</th>
                                <th class="px-6 py-3 text-center">Kategori</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($templates as $template)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-950">{{ $template->name }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-gray-800">
                                        <span class="rounded px-2 py-1 text-xs font-medium bg-slate-100 text-slate-800 uppercase">
                                            {{ $template->type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-700">
                                        &ge; {{ $template->min_recommended_percentage }}% / &ge; {{ $template->min_considered_percentage }}%
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-gray-800">
                                        {{ $template->categories_count }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $template->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $template->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.interview-templates.edit', $template) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <form method="POST" action="{{ route('admin.interview-templates.destroy', $template) }}" class="inline"
                                                  onclick="return confirm('Apakah Anda yakin ingin menghapus template ini beserta seluruh kategori, aspek, dan penilaian interview yang menggunakan template ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="font-semibold text-gray-700">Belum ada template Interview.</p>
                                        <p class="mt-1 text-sm text-gray-500">Mulai dengan mengeklik tombol Tambah Template.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($templates->hasPages())
                    <div class="border-t border-gray-100 px-6 py-4">
                        {{ $templates->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
