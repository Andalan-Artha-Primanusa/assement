<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit User') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                @include('admin.users.form', [
                    'action' => route('admin.users.update', $user),
                    'method' => 'PUT',
                    'button' => 'Update User',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
