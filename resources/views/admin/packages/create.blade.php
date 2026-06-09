<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tambah Paket Soal') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                @include('admin.packages.form', [
                    'action' => route('admin.packages.store'),
                    'button' => 'Simpan Paket',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
