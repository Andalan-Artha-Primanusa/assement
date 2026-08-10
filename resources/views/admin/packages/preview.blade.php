<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Preview Soal: {{ $package->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ \App\Models\QuestionPackage::typeLabel($package->type) }}{{ $package->level ? ' - '.$package->level : '' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.questions.create', ['question_package_id' => $package->id]) }}" class="inline-flex min-h-[44px] items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Tambah Soal</a>
                <a href="{{ route('admin.packages.questions', $package) }}" class="inline-flex min-h-[44px] items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
            </div>
        </div>
    </x-slot>

    <div class="bg-slate-50 py-6 sm:py-10">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Total Soal</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900">{{ $questions->count() }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Soal Aktif</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $questions->where('is_active', true)->count() }}</p>
                </div>
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-500">Tipe Paket</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ \App\Models\QuestionPackage::typeLabel($package->type) }}</p>
                </div>
            </div>

            <div class="space-y-5">
                @forelse ($questions as $question)
                    @php
                        $typeLabels = [
                            'multiple_choice' => 'PG',
                            'true_false' => 'Benar/Salah',
                            'essay' => 'Essay',
                            'upload' => 'Portfolio',
                        ];
                    @endphp
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-sm font-bold text-indigo-700">{{ $loop->iteration }}</span>
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $typeLabels[$question->type] ?? $question->type }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $question->category }}</span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($question->difficulty) }}</span>
                                @if ($package->type === \App\Models\QuestionPackage::TYPE_HR)
                                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Nilai {{ number_format($question->pointValue(), 2) }}</span>
                                @endif
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $question->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $question->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <p class="whitespace-pre-line text-base font-semibold leading-7 text-gray-950">{{ $question->text }}</p>

                        @if ($question->image)
                            <div class="mt-4">
                                <img src="{{ $question->imageUrl() }}" alt="Gambar soal" class="max-h-80 rounded-md border border-gray-200 object-contain">
                            </div>
                        @endif

                        @if ($question->isAutoScored())
                            <div class="mt-5 grid gap-3">
                                @foreach ($question->answerOptions() as $option)
                                    @if ($question->optionText($option) !== '')
                                        <label class="flex min-h-[44px] items-start gap-3 rounded-md border border-gray-200 p-3">
                                            <input type="radio" disabled class="mt-1 shrink-0 border-gray-300 text-indigo-600">
                                            <span class="text-sm sm:text-base">
                                                <span class="font-semibold uppercase text-gray-900">{{ $option }}.</span>
                                                <span class="text-gray-700">{{ $question->optionText($option) }}</span>
                                            </span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>

                            <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                                Kunci jawaban: <strong>{{ strtoupper($question->correct_option) }}. {{ $question->optionText($question->correct_option) }}</strong>
                            </div>
                        @elseif ($question->isEssay())
                            <div class="mt-5">
                                <textarea disabled rows="5" class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm text-gray-500" placeholder="Area jawaban essay peserta"></textarea>
                            </div>
                            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                Essay dinilai manual admin 0-100.
                            </div>
                        @elseif ($question->isUpload())
                            <div class="mt-5 rounded-md border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                                Area upload file peserta
                            </div>
                            <div class="mt-4 rounded-md border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800">
                                Portfolio/upload dinilai manual admin 0-100.
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="rounded-lg bg-white px-6 py-12 text-center shadow-sm">
                        <p class="text-sm text-gray-500">Belum ada soal di paket ini.</p>
                        <a href="{{ route('admin.questions.create', ['question_package_id' => $package->id]) }}" class="mt-4 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tambah Soal</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
