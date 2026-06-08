<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Soal') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                @include('admin.questions.form', [
                    'action' => route('admin.questions.update', $question),
                    'method' => 'PUT',
                    'button' => 'Update Soal',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
