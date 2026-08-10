<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-semibold text-gray-950">{{ __('Edit Paket Soal') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $package->name }} - {{ \App\Models\QuestionPackage::typeLabel($package->type) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.packages.preview', $package) }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Preview Paket</a>
                <a href="{{ route('admin.packages.index') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                @include('admin.packages.form', [
                    'action' => route('admin.packages.update', $package),
                    'method' => 'PUT',
                    'button' => 'Update Paket',
                ])
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-base font-semibold text-gray-950">Soal dalam Paket</h3>
                        <a href="{{ route('admin.packages.questions', $package) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                            Lihat semua
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ $package->questions_count }} soal tersambung ke paket ini.</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($package->questions as $question)
                            <div class="rounded-md border border-gray-200 bg-gray-50/60 p-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                    @if ($question->isAutoScored())
                                        <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Kunci {{ strtoupper($question->correct_option) }}</span>
                                        @if ($package->type === \App\Models\QuestionPackage::TYPE_HR)
                                            <span class="rounded-full bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700">Nilai {{ number_format($question->pointValue(), 2) }}</span>
                                        @endif
                                    @else
                                        <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">{{ $question->isEssay() ? 'Essay' : 'Upload' }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm font-medium text-gray-900">{{ $question->text }}</p>
                            </div>
                        @empty
                            <p class="rounded-md bg-gray-50 p-4 text-sm text-gray-500">Belum ada soal di paket ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-base font-semibold text-gray-950">User Paket Ini</h3>
                        <a href="{{ route('admin.users.index', ['package' => $package->id]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
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
