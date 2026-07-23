<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                @if ($selectedType === 'she')
                    {{ __('Review SHE Assessment') }}
                @else
                    {{ __('Review Assessment') }} {{ $selectedType === 'mekanik' ? 'Mekanik' : 'Operator' }}
                @endif
            </h2>
            <a href="{{ route('admin.she-review.index', ['type' => $selectedType]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali</a>
        </div>
    </x-slot>

    @php use Illuminate\Support\Facades\Storage; @endphp

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Peserta</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assessment->user->name }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Paket</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assessment->questionPackage?->name ?? '-' }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Status</p>
                    @if ($assessment->isPendingReview())
                        <span class="inline-block mt-1 rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">Perlu Review</span>
                    @elseif ($assessment->isGraded())
                        <p class="mt-1 text-lg font-semibold text-indigo-700">{{ number_format($assessment->score, 2) }}</p>
                    @endif
                </div>
            </div>

            @if ($selectedType === 'she')
                {{-- SHE: Grading form --}}
                <form method="POST" action="{{ route('admin.she-review.grade', $assessment) }}" class="space-y-6">
                    @csrf

                    @foreach ($answers as $answer)
                        @php($question = $answer->question)
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
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                    </div>

                                    <p class="whitespace-pre-line text-sm sm:text-base font-medium text-gray-900">{{ $question->text }}</p>

                                    @if ($question->isUpload() && $answer->file_path)
                                        <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                            <p class="text-sm font-medium text-gray-700 mb-2">File yang diupload:</p>
                                            @if (str_ends_with($answer->file_path, '.pdf'))
                                                <a href="{{ Storage::url($answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                    Lihat PDF
                                                </a>
                                            @elseif (in_array(pathinfo($answer->file_path, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                <img src="{{ Storage::url($answer->file_path) }}" alt="Upload" class="max-w-md rounded-lg border border-gray-200">
                                            @else
                                                <a href="{{ Storage::url($answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                    Download File
                                                </a>
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
                        @php($question = $answer->question)
                        <div class="bg-white p-4 sm:p-6 shadow-sm sm:rounded-lg">
                            <div class="flex items-start gap-3 sm:gap-4">
                                <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs sm:text-sm font-semibold text-indigo-700">
                                    {{ $answer->position }}
                                </div>
                                <div class="w-full min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        @if ($question->isMultipleChoice())
                                            <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">MC</span>
                                        @endif
                                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                    </div>

                                    <p class="whitespace-pre-line text-sm sm:text-base font-medium text-gray-900">{{ $question->text }}</p>

                                    @if ($question->isMultipleChoice())
                                        <div class="mt-3 space-y-1">
                                            @foreach ($question->choices as $i => $choice)
                                                <div class="flex items-center gap-2 text-sm {{ $choice === $question->correct_choice ? 'font-semibold text-emerald-700' : 'text-gray-700' }}">
                                                    <span class="shrink-0">{{ chr(65 + $i) }}.</span>
                                                    <span>{{ $choice }}</span>
                                                    @if ($choice === $question->correct_choice)
                                                        <span class="shrink-0 text-emerald-600">(Benar)</span>
                                                    @endif
                                                    @if ($choice === $answer->answer_text && $choice !== $question->correct_choice)
                                                        <span class="shrink-0 text-rose-600">(Jawaban)</span>
                                                    @endif
                                                    @if ($choice === $answer->answer_text && $choice === $question->correct_choice)
                                                        <span class="shrink-0 text-indigo-600">(Jawaban)</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="mt-3">
                                        @if ($answer->is_correct)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                Benar
                                            </span>
                                        @elseif ($answer->is_correct === false)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                Salah
                                            </span>
                                        @endif
                                    </div>
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
