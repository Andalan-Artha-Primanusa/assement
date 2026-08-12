<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">Tambah Kategori Invite</h2>
                <p class="mt-1 text-sm text-gray-500">Buat pilihan custom seperti New Hire, Refreshment, atau kategori lain.</p>
            </div>
            <a href="{{ route('admin.operator-categories.index') }}" class="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('admin.operator-categories.form', [
                    'action' => route('admin.operator-categories.store'),
                    'method' => 'POST',
                    'button' => 'Tambah Kategori',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
