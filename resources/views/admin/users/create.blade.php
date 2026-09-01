<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">{{ __('Tambah User') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Buat peserta atau akun admin sesuai modul.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('admin.users.form_' . $formType, [
                    'action' => route('admin.users.store'),
                    'method' => 'POST',
                    'button' => $formType === 'admin' ? 'Simpan Admin' : 'Simpan Peserta',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
