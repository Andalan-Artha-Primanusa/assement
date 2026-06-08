<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Paket Soal') }}</h2>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                @include('admin.packages.form', [
                    'action' => route('admin.packages.update', $package),
                    'method' => 'PUT',
                    'button' => 'Update Paket',
                ])
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-lg font-semibold text-gray-900">Soal dalam Paket</h3>
                        <a href="{{ route('admin.questions.index', ['package' => $package->id]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            Lihat semua
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ $package->questions_count }} soal tersambung ke paket ini.</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($package->questions as $question)
                            <div class="rounded-md border border-gray-200 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                    <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Kunci {{ strtoupper($question->correct_option) }}</span>
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm font-medium text-gray-900">{{ $question->text }}</p>
                            </div>
                        @empty
                            <p class="rounded-md bg-gray-50 p-4 text-sm text-gray-500">Belum ada soal di paket ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-lg font-semibold text-gray-900">User Paket Ini</h3>
                        <a href="{{ route('admin.users.index', ['package' => $package->id]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                            Lihat semua
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ $package->users_count }} user tersambung ke paket ini.</p>

                    <div class="mt-4 divide-y divide-gray-100">
                        @forelse ($package->users as $user)
                            <div class="py-3">
                                <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                            </div>
                        @empty
                            <p class="rounded-md bg-gray-50 p-4 text-sm text-gray-500">Belum ada user di paket ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
