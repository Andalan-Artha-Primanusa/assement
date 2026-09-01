<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">{{ __('Master Site') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola daftar lokasi site untuk digunakan di seluruh sistem.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- FORM TAMBAH / EDIT --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50/50 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ isset($site) ? 'Edit Site' : 'Tambah Site Baru' }}</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ isset($site) ? route('admin.sites.update', $site) : route('admin.sites.store') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        @csrf
                        @if (isset($site))
                            @method('PUT')
                        @endif

                        <div class="flex-1">
                            <x-input-label for="code" value="Kode Site" />
                            <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $site->code ?? '')" required placeholder="Contoh: KAL-TIM" maxlength="20" />
                            <x-input-error :messages="$errors->get('code')" class="mt-1" />
                        </div>

                        <div class="flex-[2]">
                            <x-input-label for="name" value="Nama Lengkap Site" />
                            <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $site->name ?? '')" required placeholder="Contoh: Site Kalimantan Timur" />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        @if (isset($site))
                            <div class="flex items-center gap-2 self-center">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_active', $site->is_active))>
                                    <span class="text-sm text-gray-700">Aktif</span>
                                </label>
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <button class="whitespace-nowrap rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                                {{ isset($site) ? 'Update Site' : 'Tambah Site' }}
                            </button>
                            @if (isset($site))
                                <a href="{{ route('admin.sites.index') }}" class="whitespace-nowrap rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors">Batal</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- TABEL DAFTAR SITE --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Kode</th>
                                <th class="px-6 py-3">Nama Site</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-center">Jumlah User</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($sites as $s)
                                <tr class="hover:bg-gray-50 {{ $s->isHO() ? 'bg-amber-50/50' : '' }}">
                                    <td class="px-6 py-4 font-mono font-bold text-gray-900">
                                        {{ $s->code }}
                                        @if ($s->isHO())
                                            <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">PUSAT</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">{{ $s->name }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($s->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-700">
                                        {{ \App\Models\User::where('site', $s->code)->count() }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.sites.edit', $s) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            @unless ($s->isHO())
                                                <form method="POST" action="{{ route('admin.sites.destroy', $s) }}" class="inline" data-confirm
                                                      data-confirm-title="Hapus site ini?"
                                                      data-confirm-message="Site {{ $s->name }} akan dihapus dari sistem."
                                                      data-confirm-text="Ya, hapus site"
                                                      data-confirm-variant="danger">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-md bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                                </form>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada site. Tambahkan site pertama di atas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
                <p class="text-sm text-blue-800">
                    <strong>💡 Info:</strong> Site dengan kode <strong>HO</strong> (Head Office) adalah site khusus. Admin yang ditempatkan di HO akan memiliki akses untuk melihat data dari <strong>semua site</strong>.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
