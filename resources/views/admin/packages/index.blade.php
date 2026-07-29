<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">{{ __('Paket Soal') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Kelola paket per modul, level, threshold, dan peserta.</p>
            </div>
            <a href="{{ route('admin.packages.create') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah Paket</a>
        </div>
    </x-slot>

    @php
        $visibleTypes = Auth::user()->visiblePackageTypes();
        $typeLabel = fn (?string $type): string => \App\Models\QuestionPackage::typeLabel($type);
    @endphp

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-5 flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-950">{{ $packages->total() }} paket ditemukan</p>
                    <p class="mt-1 text-xs text-gray-500">Filter cepat berdasarkan tipe paket.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.packages.index') }}" class="inline-flex min-h-[36px] items-center rounded-md px-3 py-1.5 text-sm font-semibold {{ ! $selectedType ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Semua</a>
                    @foreach ($visibleTypes as $type)
                        <a href="{{ route('admin.packages.index', ['type' => $type]) }}" class="inline-flex min-h-[36px] items-center rounded-md px-3 py-1.5 text-sm font-semibold {{ $selectedType === $type ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">{{ $typeLabel($type) }}</a>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase text-gray-500">
                                <th class="px-6 py-3">Nama Paket</th>
                                <th class="px-6 py-3 text-center">Tipe</th>
                                <th class="px-6 py-3 text-center">Level</th>
                                <th class="px-6 py-3 text-center">Threshold</th>
                                <th class="px-6 py-3 text-center">Soal</th>
                                <th class="px-6 py-3 text-center">Peserta</th>
                                <th class="px-6 py-3 text-center">Status</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($packages as $package)
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-950">{{ $package->name }}</p>
                                        @if ($package->description)
                                            <p class="mt-1 max-w-md truncate text-xs text-gray-500">{{ $package->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ \App\Models\QuestionPackage::typeBadgeClasses($package->type) }}">
                                            {{ $typeLabel($package->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($package->level)
                                            <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $package->level }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs text-gray-600">
                                        @if ($package->min_score_pertimbangan || $package->min_score_lolos)
                                            <span class="font-semibold text-amber-700">{{ $package->min_score_pertimbangan ?? '-' }}</span>
                                            <span class="mx-1 text-gray-300">/</span>
                                            <span class="font-semibold text-emerald-700">{{ $package->min_score_lolos ?? '-' }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-gray-800">{{ $package->questions_count }}</td>
                                    <td class="px-6 py-4 text-center font-semibold text-gray-800">{{ $package->users_count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $package->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                            @if ($package->has_segments)
                                                <span class="rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-semibold text-cyan-700">Segment</span>
                                            @endif
                                            @if ($package->is_certificate)
                                                <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">Sertifikat</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('admin.packages.questions', $package) }}" class="rounded-md bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">Soal</a>
                                            <a href="{{ route('admin.packages.edit', $package) }}" class="rounded-md bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">Edit</a>
                                            <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Hapus paket {{ $package->name }}?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-md bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <p class="font-semibold text-gray-700">Belum ada paket soal.</p>
                                        <p class="mt-1 text-sm text-gray-500">Mulai dari tombol Tambah Paket.</p>
                                    </td>
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
