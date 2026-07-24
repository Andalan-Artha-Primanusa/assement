<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Hasil Assessment') }}</h2>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali ke dashboard</a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($assessment->auto_submitted_at)
                <div class="mb-6 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Assessment otomatis dianggap selesai pada {{ $assessment->auto_submitted_at->format('d M Y H:i') }} karena pelanggaran melebihi batas.
                </div>
            @endif

            @if ($assessment->isPendingReview())
                <div class="mb-6 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Assessment sedang menunggu review oleh admin untuk soal Essay/Upload. Nilai akhir akan ditampilkan setelah review selesai.
                </div>
            @endif

            @php
                $package = $assessment->questionPackage;
                $grade = $package ? $package->getGrade((float) $assessment->score) : null;
                $showCertificate = $package && $package->is_certificate && $grade && in_array($grade, ['Lolos', 'Dipertimbangkan']);
            @endphp

            {{-- Info Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Peserta</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">{{ $assessment->user->name }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Paket soal</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">
                        {{ $package?->name ?? 'Semua paket' }}
                    </p>
                    @if ($package)
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $package->type === 'she' ? 'SHE' : ucfirst($package->type) }}
                            | Threshold: >= {{ $package->min_score_pertimbangan ?? '-' }} / {{ $package->min_score_lolos ?? '-' }}
                        </p>
                    @endif
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Jawaban benar</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ $assessment->correct_answers }}/{{ $assessment->total_questions }}</p>
                </div>
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm text-gray-500">Nilai</p>
                    @if ($assessment->isGraded())
                        <p class="mt-2 text-3xl font-semibold text-indigo-700">{{ number_format($assessment->score, 2) }}</p>
                    @else
                        <p class="mt-2 text-3xl font-semibold text-gray-300">--</p>
                    @endif
                </div>
            </div>

            {{-- Grade Result --}}
            @if ($grade && $assessment->isGraded())
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @if ($grade === 'Lolos')
                        <div class="bg-gradient-to-r from-emerald-500 to-green-500 p-8 text-center text-white">
                            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold">Lolos</h3>
                            <p class="mt-2 text-sm text-emerald-100">Selamat! Anda dinyatakan <strong>LOLOS</strong> dalam assessment ini.</p>
                        </div>
                    @elseif ($grade === 'Dipertimbangkan')
                        <div class="bg-gradient-to-r from-amber-500 to-yellow-500 p-8 text-center text-white">
                            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold">Dipertimbangkan</h3>
                            <p class="mt-2 text-sm text-amber-100">Hasil Anda akan ditinjau lebih lanjut oleh tim HR.</p>
                        </div>
                    @else
                        <div class="bg-gradient-to-r from-rose-500 to-red-500 p-8 text-center text-white">
                            <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white/20">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l2-2m-2 2l-2-2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold">Tidak Lolos</h3>
                            <p class="mt-2 text-sm text-rose-100">Nilai Anda belum mencapai ambang kelulusan. Silakan hubungi admin untuk informasi lebih lanjut.</p>
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="grid gap-4 sm:grid-cols-3 text-center">
                            <div>
                                <p class="text-xs text-gray-500">Ambang Dipertimbangkan</p>
                                <p class="mt-1 text-lg font-bold text-amber-600">{{ $package->min_score_pertimbangan ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Nilai Anda</p>
                                <p class="mt-1 text-lg font-bold text-indigo-600">{{ number_format($assessment->score, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Ambang Lolos</p>
                                <p class="mt-1 text-lg font-bold text-emerald-600">{{ $package->min_score_lolos ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Hasil Penilaian Essay --}}
            @if ($assessment->hasEssayOrUploadQuestions())
                @php
                    $essayAnswers = $assessment->answers()->whereHas('question', fn($q) => $q->whereIn('type', ['essay', 'upload']))->get();
                    $gradedEssayAnswers = $essayAnswers->filter(fn($a) => $a->score !== null);
                    $allGraded = $essayAnswers->count() > 0 && $gradedEssayAnswers->count() === $essayAnswers->count();
                    $essayScore = $allGraded ? $gradedEssayAnswers->avg('score') : null;
                    $essayPass = $essayScore !== null && $essayScore >= 50;
                    $latestReview = $gradedEssayAnswers->sortByDesc('reviewed_at')->first();
                    $feedbacks = $gradedEssayAnswers->filter(fn($a) => $a->review_notes)->pluck('review_notes')->implode("\n");
                @endphp

                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Hasil Penilaian Essay</h3>
                            @if ($essayScore === null)
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                    Menunggu Penilaian
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Sudah Dinilai
                                </span>
                            @endif
                        </div>

                        @if ($essayScore === null)
                            {{-- Menunggu Penilaian --}}
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                                        <svg class="h-5 w-5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-amber-800">Menunggu Penilaian HR</p>
                                        <p class="mt-1 text-sm text-amber-700">Essay Anda telah berhasil dikirim dan saat ini sedang menunggu proses penilaian oleh tim HR. Nilai akhir assessment akan diperbarui setelah proses review selesai.</p>
                                        <p class="mt-2 text-xs text-amber-600">Silakan cek kembali secara berkala.</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Sudah Dinilai --}}
                            <div class="space-y-3">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-medium text-gray-500">Nilai Essay</p>
                                        <p class="mt-1 text-2xl font-bold {{ $essayPass ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($essayScore, 2) }}</p>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-medium text-gray-500">Status</p>
                                        <p class="mt-1 text-2xl font-bold {{ $essayPass ? 'text-emerald-600' : 'text-rose-600' }}">{{ $essayPass ? 'Lulus' : 'Tidak Lulus' }}</p>
                                    </div>
                                </div>

                                @if ($feedbacks)
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-medium text-gray-500 mb-1">Feedback HR</p>
                                        <p class="whitespace-pre-line text-sm text-gray-800 italic">"{{ $feedbacks }}"</p>
                                    </div>
                                @endif

                                @if ($latestReview && $latestReview->reviewed_at)
                                    <div class="flex items-center gap-2 text-xs text-gray-500">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>Tanggal Penilaian: <strong>{{ $latestReview->reviewed_at->format('d M Y H:i') }} WIB</strong></span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Certificate --}}
            @if ($showCertificate && $assessment->isGraded())
                <div class="mt-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-200">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Selamat! Anda Berhasil</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda berhak mendapatkan sertifikat kelulusan assessment.</p>
                        <a href="{{ route('assessment.certificate', $assessment) }}" target="_blank"
                           class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-200 transition-all hover:from-indigo-700 hover:to-blue-700 hover:shadow-lg hover:-translate-y-0.5">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Lihat Sertifikat
                        </a>
                    </div>
                </div>
            @endif

            {{-- Segment Progress --}}
            @if ($assessment->segments()->count() > 0)
                <div class="mt-6 bg-white p-6 shadow-sm sm:rounded-lg">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Progress Segment</p>
                    <div class="flex gap-3">
                        @foreach ($assessment->segments as $seg)
                            <div class="flex-1 rounded-lg border p-3 text-center
                                {{ $seg->isCompleted() ? 'border-emerald-200 bg-emerald-50' : ($seg->isInProgress() ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50') }}">
                                <p class="text-xs font-semibold {{ $seg->isCompleted() ? 'text-emerald-600' : ($seg->isInProgress() ? 'text-indigo-600' : 'text-gray-400') }}">
                                    {{ $seg->type === 'multiple_choice' ? 'PG' : ucfirst($seg->type) }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">{{ $seg->duration_minutes }} menit</p>
                                @if ($seg->completed_at)
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $seg->completed_at->format('H:i') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Detail Jawaban --}}
            @if ($assessment->answers->count() > 0)
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 sm:px-6 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700">Detail Jawaban</h3>
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

                                            @if ($answer->score !== null)
                                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">Nilai: {{ number_format($answer->score, 0) }}</span>
                                            @endif
                                        </div>

                                        <p class="whitespace-pre-line text-sm font-medium text-gray-900">{{ $question->text }}</p>

                                        @if ($question->image)
                                            <img src="{{ route('files.show', $question->image) }}" alt="Gambar soal" class="mt-2 max-h-40 rounded-md border border-gray-200 object-contain">
                                        @endif

                                        {{-- MC --}}
                                        @if ($question->isMultipleChoice())
                                            <div class="mt-2 space-y-0.5">
                                                @foreach (['a', 'b', 'c', 'd'] as $opt)
                                                    @php($optText = $question->optionText($opt))
                                                    @if ($optText)
                                                        @php($isCorrect = $question->correct_option === $opt)
                                                        @php($isSelected = $answer->selected_option === $opt)
                                                        <div class="flex items-center gap-2 text-xs rounded px-2 py-1
                                                            {{ $isCorrect ? 'bg-emerald-50 text-emerald-700 font-semibold' : '' }}
                                                            {{ $isSelected && !$isCorrect ? 'bg-rose-50 text-rose-700' : '' }}
                                                            {{ !$isCorrect && !$isSelected ? 'text-gray-500' : '' }}">
                                                            <span class="shrink-0 font-bold uppercase">{{ $opt }}.</span>
                                                            <span class="flex-1">{{ $optText }}</span>
                                                            @if ($isCorrect && $isSelected)
                                                                <span class="shrink-0 text-emerald-600">✓ Benar</span>
                                                            @elseif ($isCorrect)
                                                                <span class="shrink-0 text-emerald-500">✓</span>
                                                            @elseif ($isSelected)
                                                                <span class="shrink-0 text-rose-600 font-bold">✗ Dipilih</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- Essay --}}
                                        @if ($question->isEssay())
                                            <div class="mt-2 p-2.5 rounded-md bg-gray-50 border border-gray-200">
                                                <p class="text-xs font-semibold text-gray-500 mb-0.5">Jawaban:</p>
                                                @if ($answer->answer_text)
                                                    <p class="whitespace-pre-line text-sm text-gray-900">{{ $answer->answer_text }}</p>
                                                @else
                                                    <p class="text-sm text-gray-400 italic">Tidak ada jawaban</p>
                                                @endif
                                            </div>
                                            @if ($answer->review_notes)
                                                <p class="mt-1 text-xs text-gray-500">Catatan reviewer: {{ $answer->review_notes }}</p>
                                            @endif
                                        @endif

                                        {{-- Upload --}}
                                        @if ($question->isUpload())
                                            <div class="mt-2 p-2.5 rounded-md bg-gray-50 border border-gray-200">
                                                <p class="text-xs font-semibold text-gray-500 mb-0.5">File:</p>
                                                @if ($answer->file_path)
                                                    @if (str_ends_with(strtolower($answer->file_path), '.pdf'))
                                                        <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat PDF</a>
                                                    @elseif (in_array(strtolower(pathinfo($answer->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                        <img src="{{ route('files.show', $answer->file_path) }}" alt="Upload" class="max-w-xs rounded-md border border-gray-200">
                                                    @else
                                                        <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Download File</a>
                                                    @endif
                                                @else
                                                    <p class="text-sm text-gray-400 italic">Tidak ada file</p>
                                                @endif
                                            </div>
                                            @if ($answer->review_notes)
                                                <p class="mt-1 text-xs text-gray-500">Catatan reviewer: {{ $answer->review_notes }}</p>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Back to Dashboard Button --}}
            <div class="mt-8 text-center">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-8 py-3 text-sm font-semibold text-white shadow-sm hover:bg-black transition-all">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
