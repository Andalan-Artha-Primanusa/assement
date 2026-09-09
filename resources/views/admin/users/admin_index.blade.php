<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">Master Akun</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola akun user internal untuk akses admin per modul dan site.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
                <a href="{{ route('admin.users.create', ['type' => 'admin', 'form' => 1]) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-black">+ Tambah User</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Nama</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3 text-center">Hak Akses</th>
                                <th class="px-6 py-3">Site</th>
                                <th class="px-6 py-3">Dibuat</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($adminUsers as $user)
                                @php
                                    $roleColors = [
                                        'super_admin' => 'bg-red-50 text-red-700',
                                        'admin_mekanik' => 'bg-indigo-50 text-indigo-700',
                                        'admin_operation' => 'bg-purple-50 text-purple-700',
                                        'admin_she' => 'bg-cyan-50 text-cyan-700',
                                        'admin_hr' => 'bg-rose-50 text-rose-700',
                                    ];
                                    $roleLabels = [
                                        'super_admin' => 'Super User',
                                        'admin_mekanik' => 'User Mekanik',
                                        'admin_operation' => 'User Operator',
                                        'admin_she' => 'User SHE',
                                        'admin_hr' => 'User HR',
                                    ];
                                    $site = ($allSites ?? collect())->firstWhere('code', $user->site);
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->email }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $roleColors[$user->role] ?? 'bg-gray-50 text-gray-700' }}">
                                            {{ $roleLabels[$user->role] ?? $user->role }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        @if ($user->site)
                                            {{ $user->site }}{{ $site ? ' — '.$site->name : '' }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $user->created_at?->format('d M Y H:i') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm
                                                  data-confirm-title="Hapus akun ini?"
                                                  data-confirm-message="Akun {{ $user->name }} akan dihapus dari sistem."
                                                  data-confirm-text="Ya, hapus akun"
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
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">Belum ada akun internal.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $adminUsers->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
