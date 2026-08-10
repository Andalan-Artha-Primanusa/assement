<x-app-layout>
    @php
        $selectedTypeLabel = \App\Models\QuestionPackage::typeLabel($selectedType);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @if ($selectedType === 'she')
                    {{ __('Review SHE Assessment') }}
                @else
                    Review Assessment {{ $selectedTypeLabel }}
                @endif
            </h2>
            <a href="{{ route('admin.she-review.index', ['type' => $selectedType]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Peserta</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assessment->user->name }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Paket</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assessment->questionPackage?->name ?? '-' }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Nilai Akhir</p>
                    @if ($assessment->isGraded())
                        <p class="mt-1 text-lg font-semibold {{ ($assessment->score ?? 0) >= 50 ? 'text-emerald-700' : 'text-rose-700' }}">{{ number_format($assessment->score, 2) }}</p>
                    @else
                        <span class="mt-1 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Menunggu Review SHE</span>
                        <p class="mt-2 text-xs leading-5 text-gray-500">Nilai akhir muncul setelah essay/upload diberi nilai.</p>
                    @endif
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Status</p>
                    @if ($assessment->isPendingReview())
                        <span class="inline-block mt-1 rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">Perlu Review</span>
                    @elseif ($assessment->isGraded())
                        <span class="inline-block mt-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">Selesai Direview</span>
                    @else
                        <span class="inline-block mt-1 rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-600">{{ $assessment->status }}</span>
                    @endif
                </div>
            </div>

            @if ($selectedType === 'she')
                {{-- SHE: Grading form --}}
                <div class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                    Rumus nilai SHE: PG dihitung sebagai persentase jawaban benar, Essay dinilai manual 0-100, dan Portfolio dinilai manual 0-100. Nilai akhir adalah rata-rata komponen PG, Essay, dan Portfolio yang tersedia.
                </div>
                <form method="POST" action="{{ route('admin.she-review.grade', $assessment) }}" class="space-y-6" data-confirm
                      data-confirm-title="Simpan nilai assessment?"
                      data-confirm-message="Pastikan semua nilai essay/upload dan catatan review sudah benar sebelum disimpan."
                      data-confirm-text="Ya, simpan nilai">
                    @csrf

                    @foreach ($answers as $answer)
                        @php
                            $question = $answer->question;
                        @endphp
                        <div class="bg-white p-4 sm:p-6 shadow-sm sm:rounded-lg">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs sm:text-sm font-semibold text-indigo-700">
                                    {{ $answer->position }}
                                </div>
                                <div class="w-full min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        @if ($question->isEssay())
                                            <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">Essay</span>
                                        @elseif ($question->isUpload())
                                            <span class="rounded-full bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700">Upload</span>
                                        @endif
                                        @if ($question->category)
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                        @endif
                                    </div>

                                    <p class="whitespace-pre-line text-sm sm:text-base font-medium text-gray-900">{{ $question->text }}</p>

                                    @if ($question->image)
                                        <div class="mt-3">
                                            <img src="{{ route('files.show', $question->image) }}" alt="Gambar soal" class="max-h-64 rounded-md border border-gray-200 object-contain">
                                        </div>
                                    @endif

                                    @if ($question->isUpload())
                                        @php
                                            $uploadedFileUrl = $answer->file_path ? route('files.show', $answer->file_path) : null;
                                            $uploadedFileName = $answer->file_path ? basename($answer->file_path) : null;
                                            $uploadedFileExtension = $answer->file_path ? strtolower(pathinfo($answer->file_path, PATHINFO_EXTENSION)) : null;
                                            $uploadedFileIsImage = in_array($uploadedFileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                        @endphp
                                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                            <p class="text-sm font-medium text-gray-700 mb-2">File yang diupload:</p>
                                            @if ($uploadedFileUrl)
                                                @if ($uploadedFileIsImage)
                                                    <img src="{{ $uploadedFileUrl }}" alt="Upload" class="mb-3 max-w-md rounded-lg border border-gray-200">
                                                @endif
                                                <div class="flex flex-col gap-3 rounded-md border border-indigo-100 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-gray-900">{{ $uploadedFileName }}</p>
                                                        <p class="mt-0.5 text-xs text-gray-500">Klik Lihat File untuk membuka hasil upload peserta.</p>
                                                    </div>
                                                    <div class="flex shrink-0 gap-2">
                                                        <a href="{{ $uploadedFileUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                            Lihat File
                                                        </a>
                                                        <a href="{{ $uploadedFileUrl }}?download=1" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                            Download
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm italic text-amber-700">Belum ada file diupload peserta.</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($question->isEssay() && $answer->answer_text)
                                        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-sm font-medium text-gray-700 mb-2">Jawaban:</p>
                                            <p class="whitespace-pre-line text-sm text-gray-900">{{ $answer->answer_text }}</p>
                                        </div>
                                    @endif

                                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Nilai (0-100)</label>
                                            <input type="number" name="scores[{{ $answer->id }}]" min="0" max="100" step="0.01"
                                                   value="{{ old('scores.'.$answer->id, $answer->score) }}"
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                   required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                                            <input type="text" name="notes[{{ $answer->id }}]"
                                                   value="{{ old('notes.'.$answer->id, $answer->review_notes) }}"
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                   placeholder="Opsional">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.she-review.index', ['type' => $selectedType]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</a>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Simpan Nilai</button>
                    </div>
                </form>
            @else
                {{-- Mekanik/Operator: Read-only view --}}
                <div class="space-y-6">
                    @foreach ($assessment->answers as $answer)
                        @php
                            $question = $answer->question;
                        @endphp
                        <div class="bg-white p-4 sm:p-6 shadow-sm sm:rounded-lg">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs sm:text-sm font-semibold text-indigo-700">
                                    {{ $answer->position }}
                                </div>
                                <div class="w-full min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        @if ($question->isMultipleChoice())
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">PG</span>
                                        @elseif ($question->isEssay())
                                            <span class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">Essay</span>
                                        @elseif ($question->isUpload())
                                            <span class="rounded-full bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700">Upload</span>
                                        @endif
                                        @if ($question->category)
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                        @endif
                                    </div>

                                    <p class="whitespace-pre-line text-sm sm:text-base font-medium text-gray-900">{{ $question->text }}</p>

                                    @if ($question->image)
                                        <div class="mt-3">
                                            <img src="{{ route('files.show', $question->image) }}" alt="Gambar soal" class="max-h-64 rounded-md border border-gray-200 object-contain">
                                        </div>
                                    @endif

                                    @if ($question->isMultipleChoice())
                                        <div class="mt-3 space-y-1">
                                            @foreach (['a', 'b', 'c', 'd'] as $opt)
                                                @php
                                                    $optText = $question->optionText($opt);
                                                @endphp
                                                @if ($optText)
                                                    @php
                                                        $isSelected = $answer->selected_option === $opt;
                                                        $isCorrect = $question->correct_option === $opt;
                                                    @endphp
                                                    <div class="flex items-center gap-2 text-sm rounded-md px-2 py-1
                                                        {{ $isCorrect ? 'bg-emerald-50 font-semibold text-emerald-700' : '' }}
                                                        {{ $isSelected && !$isCorrect ? 'bg-rose-50 text-rose-700' : '' }}
                                                        {{ !$isCorrect && !$isSelected ? 'text-gray-700' : '' }}">
                                                        <span class="shrink-0 font-semibold uppercase">{{ $opt }}.</span>
                                                        <span>{{ $optText }}</span>
                                                        @if ($isCorrect)
                                                            <span class="shrink-0 text-emerald-600 text-xs font-semibold">(Benar)</span>
                                                        @endif
                                                        @if ($isSelected)
                                                            <span class="shrink-0 text-xs font-semibold {{ $isCorrect ? 'text-emerald-600' : 'text-rose-600' }}">(Dipilih)</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>

                                        <div class="mt-3">
                                            @if ($answer->is_correct)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    Benar
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    Salah
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($question->isEssay() && $answer->answer_text)
                                        <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-sm font-medium text-gray-700 mb-1">Jawaban Essay:</p>
                                            <p class="whitespace-pre-line text-sm text-gray-900">{{ $answer->answer_text }}</p>
                                        </div>
                                    @endif

                                    @if ($question->isUpload())
                                        @php
                                            $uploadedFileUrl = $answer->file_path ? route('files.show', $answer->file_path) : null;
                                            $uploadedFileName = $answer->file_path ? basename($answer->file_path) : null;
                                            $uploadedFileExtension = $answer->file_path ? strtolower(pathinfo($answer->file_path, PATHINFO_EXTENSION)) : null;
                                            $uploadedFileIsImage = in_array($uploadedFileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                        @endphp
                                        <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                            <p class="text-sm font-medium text-gray-700 mb-2">File Upload:</p>
                                            @if ($uploadedFileUrl)
                                                @if ($uploadedFileIsImage)
                                                    <img src="{{ $uploadedFileUrl }}" alt="Upload" class="mb-3 max-w-md rounded-lg border border-gray-200">
                                                @endif
                                                <div class="flex flex-col gap-3 rounded-md border border-indigo-100 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-gray-900">{{ $uploadedFileName }}</p>
                                                        <p class="mt-0.5 text-xs text-gray-500">Klik Lihat File untuk membuka hasil upload peserta.</p>
                                                    </div>
                                                    <div class="flex shrink-0 gap-2">
                                                        <a href="{{ $uploadedFileUrl }}" target="_blank" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                            Lihat File
                                                        </a>
                                                        <a href="{{ $uploadedFileUrl }}?download=1" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                            Download
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-sm italic text-amber-700">Belum ada file diupload peserta.</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($answer->score !== null)
                                        <div class="mt-3">
                                            <span class="text-sm font-medium text-gray-700">Nilai: </span>
                                            <span class="text-sm font-bold text-indigo-700">{{ number_format($answer->score, 2) }}</span>
                                            @if ($answer->review_notes)
                                                <span class="text-sm text-gray-500 ml-2">({{ $answer->review_notes }})</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-end">
                        <a href="{{ route('admin.she-review.index', ['type' => $selectedType]) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Kembali</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
