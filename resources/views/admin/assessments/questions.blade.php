<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Soal Assessment: {{ $assessment->user->name }}</h2>
            <a href="{{ route('admin.assessments.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                    <span><strong>Peserta:</strong> {{ $assessment->user->name }}</span>
                    <span><strong>Email:</strong> {{ $assessment->user->email }}</span>
                    <span><strong>Paket:</strong> {{ $assessment->questionPackage?->name ?? '-' }}</span>
                    <span><strong>Dimulai:</strong> {{ $assessment->started_at?->format('d M Y H:i') }}</span>
                    <span><strong>Selesai:</strong> {{ $assessment->submitted_at?->format('d M Y H:i') ?? '-' }}</span>
                    <span><strong>Nilai:</strong> {{ $assessment->score ? number_format($assessment->score, 2).'%' : '-' }}</span>
                </div>
            </div>

            @foreach ($assessment->answers as $answer)
                @php($question = $answer->question)
                <div class="mb-4 bg-white p-4 sm:p-6 shadow-sm sm:rounded-lg">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div class="flex h-8 w-8 sm:h-9 sm:w-9 shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs sm:text-sm font-semibold text-indigo-700">
                            {{ $answer->position }}
                        </div>
                        <div class="w-full min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                @php
                                    $typeColors = ['multiple_choice' => 'bg-gray-100 text-gray-700', 'essay' => 'bg-blue-50 text-blue-700', 'upload' => 'bg-purple-50 text-purple-700'];
                                    $typeLabels = ['multiple_choice' => 'MC', 'essay' => 'Essay', 'upload' => 'Upload'];
                                @endphp
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $typeColors[$question->type] ?? '' }}">{{ $typeLabels[$question->type] ?? $question->type }}</span>
                            </div>
                            <p class="whitespace-pre-line text-sm sm:text-base font-medium text-gray-900">{{ $question->text }}</p>

                            @if ($question->image)
                                <div class="mt-3">
                                    <img src="{{ Storage::disk('public')->url($question->image) }}" alt="Gambar soal" class="max-h-48 rounded-md border border-gray-200 object-contain">
                                </div>
                            @endif

                            @if ($question->isMultipleChoice())
                                <div class="mt-4 grid gap-2">
                                    @foreach (['a', 'b', 'c', 'd'] as $option)
                                        @php
                                            $isSelected = $answer->selected_option === $option;
                                            $isCorrect = $question->correct_option === $option;
                                            $bgClass = match(true) {
                                                $isSelected && $isCorrect => 'border-emerald-300 bg-emerald-50',
                                                $isSelected && !$isCorrect => 'border-rose-300 bg-rose-50',
                                                $isCorrect => 'border-emerald-200 bg-emerald-50/50',
                                                default => 'border-gray-200',
                                            };
                                        @endphp
                                        <div class="flex items-center gap-3 rounded-md border {{ $bgClass }} p-3 text-sm">
                                            <span class="font-semibold uppercase text-gray-900">{{ $option }}.</span>
                                            <span class="text-gray-700">{{ $question->optionText($option) }}</span>
                                            @if ($isCorrect)
                                                <span class="ml-auto text-xs font-semibold text-emerald-600">&#10003; Benar</span>
                                            @endif
                                            @if ($isSelected && !$isCorrect)
                                                <span class="ml-auto text-xs font-semibold text-rose-600">Jawaban</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif ($question->isEssay())
                                <div class="mt-3 rounded-md bg-gray-50 p-3">
                                    <p class="text-xs font-semibold text-gray-500">Jawaban Peserta:</p>
                                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ $answer->answer_text ?? '-' }}</p>
                                </div>
                            @elseif ($question->isUpload())
                                <div class="mt-3 rounded-md bg-gray-50 p-3">
                                    <p class="text-xs font-semibold text-gray-500">File Upload:</p>
                                    @if ($answer->file_path)
                                        <a href="{{ asset('storage/'.$answer->file_path) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            Lihat File &#8599;
                                        </a>
                                    @else
                                        <p class="text-sm text-gray-500">Tidak ada file</p>
                                    @endif
                                </div>
                            @endif

                            @if ($answer->score !== null)
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-500">Skor:</span>
                                    <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ $answer->score }}</span>
                                    @if ($answer->review_notes)
                                        <span class="text-xs text-gray-500">- {{ $answer->review_notes }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
