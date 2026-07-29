<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Jawaban: {{ $user->name }}</h2>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali ke User</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid gap-4 sm:grid-cols-3 mb-6">
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Nama</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $user->name }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $user->email }}</p>
                </div>
                <div class="bg-white p-4 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Total Assessment Selesai</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $assessments->count() }}</p>
                </div>
            </div>

            @forelse ($assessments as $assessment)
                @php
                    $totalQ = $assessment->answers->count();
                    $correctQ = $assessment->answers->where('is_correct', true)->count();
                    $wrongQ = $assessment->answers->where('is_correct', false)->count();
                    $score = $assessment->score ?? 0;
                    $packageType = $assessment->questionPackage?->type ?? '-';
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg mb-6 overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 bg-gray-50 px-4 sm:px-6 py-3 border-b border-gray-200">
                        <div class="flex flex-wrap items-center gap-3">
                            <h3 class="font-semibold text-gray-900">{{ $assessment->questionPackage?->name ?? 'Paket Tidak Diketahui' }}</h3>
                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-600 uppercase">{{ $packageType }}</span>
                            @if ($assessment->isSubmitted() && ! $assessment->isPendingReview())
                                <span class="rounded-full {{ $score >= 50 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }} px-2.5 py-0.5 text-xs font-bold">
                                    Nilai: {{ number_format($score, 2) }}
                                </span>
                            @elseif ($assessment->isPendingReview())
                                <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-700">
                                    Menunggu Review SHE
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span>Dikirim: {{ $assessment->submitted_at?->format('d M Y H:i') }}</span>
                            <span class="text-emerald-600 font-semibold">{{ $correctQ }} benar</span>
                            <span class="text-rose-600 font-semibold">{{ $wrongQ }} salah</span>
                            <span>/ {{ $totalQ }} soal</span>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($assessment->answers as $answer)
                            @php($question = $answer->question)
                            @if (!$question) @continue @endif

                            <div class="px-4 sm:px-6 py-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                        {{ $answer->is_correct ? 'bg-emerald-100 text-emerald-700' : ($answer->is_correct === false ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $answer->position }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            @if ($question->isMultipleChoice())
                                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">PG</span>
                                            @elseif ($question->isEssay())
                                                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Essay</span>
                                            @elseif ($question->isUpload())
                                                <span class="rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">Upload</span>
                                            @endif
                                            @if ($question->category)
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                            @endif

                                            @if ($question->isMultipleChoice())
                                                @if ($answer->is_correct)
                                                    <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        Benar
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-0.5 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700">
                                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        Salah
                                                    </span>
                                                @endif
                                            @endif
                                        </div>

                                        <p class="whitespace-pre-line text-sm font-medium text-gray-900">{{ $question->text }}</p>

                                        @if ($question->image)
                                            <img src="{{ route('files.show', $question->image) }}" alt="Gambar soal" class="mt-2 max-h-48 rounded-md border border-gray-200 object-contain">
                                        @endif

                                        {{-- MC: Tampilkan semua pilihan --}}
                                        @if ($question->isMultipleChoice())
                                            <div class="mt-3 space-y-1">
                                                @foreach (['a', 'b', 'c', 'd'] as $opt)
                                                    @php($optText = $question->optionText($opt))
                                                    @if ($optText)
                                                        @php($isCorrect = $question->correct_option === $opt)
                                                        @php($isSelected = $answer->selected_option === $opt)
                                                        <div class="flex items-center gap-2 text-sm rounded-md px-3 py-1.5 border
                                                            {{ $isCorrect ? 'border-emerald-300 bg-emerald-50 text-emerald-800 font-semibold' : '' }}
                                                            {{ $isSelected && !$isCorrect ? 'border-rose-300 bg-rose-50 text-rose-800' : '' }}
                                                            {{ !$isCorrect && !$isSelected ? 'border-gray-200 text-gray-600' : '' }}">
                                                            <span class="shrink-0 font-bold uppercase">{{ $opt }}.</span>
                                                            <span class="flex-1">{{ $optText }}</span>
                                                            @if ($isCorrect && $isSelected)
                                                                <span class="shrink-0 text-xs font-bold text-emerald-600">✓ Benar & Dipilih</span>
                                                            @elseif ($isCorrect)
                                                                <span class="shrink-0 text-xs font-semibold text-emerald-600">✓ Jawaban Benar</span>
                                                            @elseif ($isSelected)
                                                                <span class="shrink-0 text-xs font-bold text-rose-600">✗ Dipilih (Salah)</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Essay: Tampilkan jawaban --}}
                                        @if ($question->isEssay())
                                            <div class="mt-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                                                <p class="text-xs font-semibold text-gray-500 mb-1">Jawaban Essay:</p>
                                                @if ($answer->answer_text)
                                                    <p class="whitespace-pre-line text-sm text-gray-900">{{ $answer->answer_text }}</p>
                                                @else
                                                    <p class="text-sm text-gray-400 italic">Tidak ada jawaban</p>
                                                @endif
                                            </div>
                                            @if ($answer->score !== null)
                                                <div class="mt-2">
                                                    <span class="text-xs font-semibold text-gray-500">Nilai: </span>
                                                    <span class="text-xs font-bold text-indigo-700">{{ number_format($answer->score, 2) }}</span>
                                                    @if ($answer->review_notes)
                                                        <span class="text-xs text-gray-500 ml-2">— {{ $answer->review_notes }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endif

                                        {{-- Upload: Tampilkan file --}}
                                        @if ($question->isUpload())
                                            <div class="mt-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                                                <p class="text-xs font-semibold text-gray-500 mb-1">File Upload:</p>
                                                @if ($answer->file_path)
                                                    @if (str_ends_with(strtolower($answer->file_path), '.pdf'))
                                                        <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat PDF</a>
                                                    @elseif (in_array(strtolower(pathinfo($answer->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                        <img src="{{ route('files.show', $answer->file_path) }}" alt="Upload" class="max-w-md rounded-lg border border-gray-200">
                                                    @else
                                                        <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Download File</a>
                                                    @endif
                                                @else
                                                    <p class="text-sm text-gray-400 italic">Tidak ada file</p>
                                                @endif
                                            </div>
                                            @if ($answer->score !== null)
                                                <div class="mt-2">
                                                    <span class="text-xs font-semibold text-gray-500">Nilai: </span>
                                                    <span class="text-xs font-bold text-indigo-700">{{ number_format($answer->score, 2) }}</span>
                                                    @if ($answer->review_notes)
                                                        <span class="text-xs text-gray-500 ml-2">— {{ $answer->review_notes }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white p-10 shadow-sm sm:rounded-lg text-center text-gray-500">
                    Belum ada assessment yang diselesaikan.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
