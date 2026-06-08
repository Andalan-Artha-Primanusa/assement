<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('CMS Paket Soal') }}</h2>
            <a href="{{ route('admin.packages.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah Paket</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Nama Paket</th>
                                <th class="px-6 py-3">Deskripsi</th>
                                <th class="px-6 py-3">Jumlah Soal</th>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Pembuat</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($packages as $package)
                                <tr>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $package->name }}</td>
                                    <td class="max-w-xs px-6 py-4 text-gray-700">
                                        <p class="line-clamp-2">{{ $package->description ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $package->questions_count }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $package->users_count }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $package->creator?->name ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $package->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.packages.edit', $package) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                            <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Hapus paket {{ $package->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="font-medium text-rose-600 hover:text-rose-800">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada paket soal.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $packages->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
