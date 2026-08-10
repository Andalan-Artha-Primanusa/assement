<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detail Assessment: {{ $assessment->user->name }}</h2>
            <a href="{{ route('admin.assessments.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali</a>
        </div>
    </x-slot>

    @php
        $package = $assessment->questionPackage;
        $isSheAssessment = $package?->type === \App\Models\QuestionPackage::TYPE_SHE;
        $isHrAssessment = $package?->type === \App\Models\QuestionPackage::TYPE_HR;
        $grade = $package ? $package->getGrade((float) $assessment->score) : null;

        $mcAnswers = $assessment->answers->filter(fn($a) => $a->question && $a->question->isMultipleChoice());
        $essayAnswers = $assessment->answers->filter(fn($a) => $a->question && $a->question->isEssay());
        $uploadAnswers = $assessment->answers->filter(fn($a) => $a->question && $a->question->isUpload());

        $mcTotalPoints = $isHrAssessment ? $mcAnswers->sum(fn($a) => $a->question?->pointValue() ?? 1) : null;
        $mcCorrectPoints = $isHrAssessment ? $mcAnswers->where('is_correct', true)->sum(fn($a) => $a->question?->pointValue() ?? 1) : null;
        $mcScore = $mcAnswers->count() > 0
            ? ($isHrAssessment && $mcTotalPoints > 0
                ? round(($mcCorrectPoints / $mcTotalPoints) * 100, 2)
                : round($mcAnswers->where('is_correct', true)->count() / $mcAnswers->count() * 100, 2))
            : null;
        $sheScores = $isSheAssessment ? \App\Support\SheScore::calculate($assessment->answers) : null;
        if ($sheScores) {
            $mcScore = $sheScores['pg'];
        }
        $mcCorrect = $mcAnswers->where('is_correct', true)->count();

        $essayGraded = $essayAnswers->isNotEmpty() && $essayAnswers->every(fn($a) => $a->score !== null);
        $essayScore = $sheScores ? $sheScores['essay'] : ($essayGraded ? round($essayAnswers->avg('score'), 2) : null);

        $uploadGraded = $uploadAnswers->isNotEmpty() && $uploadAnswers->every(fn($a) => $a->score !== null);
        $uploadScore = $sheScores ? $sheScores['upload'] : ($uploadGraded ? round($uploadAnswers->avg('score'), 2) : null);

        $needsReview = $assessment->answers->filter(fn($a) => $a->question && in_array($a->question->type, ['essay', 'upload']) && $a->score === null)->count();
    @endphp

    <div class="py-6 sm:py-12 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Grade Banner --}}
            @if ($grade && $assessment->isGraded())
                @if ($grade === 'Lolos')
                    <div class="mb-6 rounded-xl bg-gradient-to-r from-emerald-500 to-green-500 p-6 text-center text-white shadow-lg shadow-emerald-200">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold">Lolos</h3>
                        <p class="mt-1 text-sm text-emerald-100">Peserta dinyatakan <strong>LOLOS</strong> dalam assessment ini.</p>
                    </div>
                @elseif ($grade === 'Dipertimbangkan')
                    <div class="mb-6 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 p-6 text-center text-white shadow-lg shadow-amber-200">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold">Dipertimbangkan</h3>
                        <p class="mt-1 text-sm text-amber-100">Hasil akan ditinjau lebih lanjut oleh tim HR.</p>
                    </div>
                @else
                    <div class="mb-6 rounded-xl bg-gradient-to-r from-rose-500 to-red-500 p-6 text-center text-white shadow-lg shadow-rose-200">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-white/20">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l2-2m-2 2l-2-2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold">Tidak Lolos</h3>
                        <p class="mt-1 text-sm text-rose-100">Nilai belum mencapai ambang kelulusan.</p>
                    </div>
                @endif
            @elseif ($assessment->isPendingReview())
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100">
                        <svg class="h-5 w-5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold">Menunggu Penilaian</p>
                        <p class="text-amber-700 text-xs mt-0.5">Masih ada {{ $needsReview }} soal essay/upload yang belum dinilai.</p>
                    </div>
                </div>
            @endif

            {{-- Info & Score Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white p-5 shadow-sm rounded-xl">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Peserta</p>
                    <p class="mt-1 text-base font-bold text-gray-900">{{ $assessment->user->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ $assessment->user->email }}</p>
                </div>
                <div class="bg-white p-5 shadow-sm rounded-xl">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Paket Soal</p>
                    <p class="mt-1 text-base font-bold text-gray-900">{{ $package?->name ?? '-' }}</p>
                    @if ($package)
                        <p class="text-xs text-gray-400">{{ \App\Models\QuestionPackage::typeLabel($package->type) }}</p>
                    @endif
                </div>
                <div class="bg-white p-5 shadow-sm rounded-xl">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Jawaban Benar</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $assessment->correct_answers }}<span class="text-sm font-medium text-gray-400">/{{ $assessment->total_questions }}</span></p>
                    @if ($mcAnswers->count() > 0)
                        <p class="text-xs text-gray-400">PG: {{ number_format($mcScore, 0) }}%</p>
                    @endif
                </div>
                <div class="bg-white p-5 shadow-sm rounded-xl">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Nilai Akhir</p>
                    @if ($assessment->isGraded() || ($assessment->isSubmitted() && ! $assessment->isPendingReview()))
                        <p class="mt-1 text-2xl font-bold text-indigo-600">{{ number_format($assessment->score, 2) }}</p>
                    @elseif ($assessment->isPendingReview())
                        <span class="mt-2 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Menunggu Review SHE</span>
                        <p class="mt-2 text-xs leading-5 text-gray-500">Nilai final muncul setelah essay/upload dinilai.</p>
                    @else
                        <p class="mt-1 text-2xl font-bold text-gray-300">--</p>
                    @endif
                    @if ($grade)
                        <span class="inline-flex items-center mt-1 rounded-full px-2 py-0.5 text-xs font-semibold
                            {{ $grade === 'Lolos' ? 'bg-emerald-100 text-emerald-700' : ($grade === 'Dipertimbangkan' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                            {{ $grade }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Score Breakdown --}}
            @if ($mcScore !== null || ($isSheAssessment && ($essayScore !== null || $uploadScore !== null)))
                <div class="bg-white p-6 shadow-sm rounded-xl mb-6">
                    <h3 class="text-sm font-bold text-gray-900 mb-4">Rincian Skor</h3>
                    <div class="grid gap-4 {{ $isSheAssessment ? 'sm:grid-cols-3' : 'sm:grid-cols-1' }}">
                        {{-- MC --}}
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 text-center">
                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-blue-50">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <p class="text-xs text-gray-500 font-medium">Multiple Choice</p>
                            @if ($mcScore !== null)
                                <p class="text-2xl font-bold mt-1" style="color: #2563EB">{{ number_format($mcScore, 2) }}</p>
                                @if ($isHrAssessment)
                                    <p class="text-xs text-gray-400">{{ number_format($mcCorrectPoints, 2) }}/{{ number_format($mcTotalPoints, 2) }} poin</p>
                                @else
                                    <p class="text-xs text-gray-400">{{ $mcCorrect }}/{{ $mcAnswers->count() }} benar</p>
                                @endif
                            @else
                                <p class="text-2xl font-bold text-gray-300 mt-1">--</p>
                            @endif
                        </div>
                        @if ($isSheAssessment)
                            {{-- Essay --}}
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 text-center">
                                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-amber-50">
                                    <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">Essay</p>
                                @if ($essayScore !== null)
                                    <p class="text-2xl font-bold mt-1" style="color: #F59E0B">{{ number_format($essayScore, 2) }}</p>
                                    <p class="text-xs text-gray-400">Rata-rata</p>
                                @elseif ($essayAnswers->isNotEmpty())
                                    <p class="text-sm font-semibold mt-1" style="color: #D97706">Menunggu</p>
                                    <p class="text-xs text-gray-400">{{ $essayAnswers->count() }} soal</p>
                                @else
                                    <p class="text-2xl font-bold text-gray-300 mt-1">--</p>
                                    <p class="text-xs text-gray-400">Tidak ada</p>
                                @endif
                            </div>
                            {{-- Upload --}}
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4 text-center">
                                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-purple-50">
                                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-xs text-gray-500 font-medium">Portfolio</p>
                                @if ($uploadScore !== null)
                                    <p class="text-2xl font-bold mt-1" style="color: #8B5CF6">{{ number_format($uploadScore, 2) }}</p>
                                    <p class="text-xs text-gray-400">Rata-rata</p>
                                @elseif ($uploadAnswers->isNotEmpty())
                                    <p class="text-sm font-semibold mt-1" style="color: #7C3AED">Menunggu</p>
                                    <p class="text-xs text-gray-400">{{ $uploadAnswers->count() }} soal</p>
                                @else
                                    <p class="text-2xl font-bold text-gray-300 mt-1">--</p>
                                    <p class="text-xs text-gray-400">Tidak ada</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($grade && $package)
                        <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-4 text-sm">
                                <span class="text-gray-500">Threshold Dipertimbangkan: <strong class="text-amber-600">{{ $package->min_score_pertimbangan ?? '-' }}</strong></span>
                                <span class="text-gray-500">Threshold Lolos: <strong class="text-emerald-600">{{ $package->min_score_lolos ?? '-' }}</strong></span>
                            </div>
                            @if ($assessment->isGraded())
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-bold
                                    {{ $grade === 'Lolos' ? 'bg-emerald-100 text-emerald-700' : ($grade === 'Dipertimbangkan' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                    @if ($grade === 'Lolos')
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endif
                                    {{ $grade }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Segment Progress --}}
            @if ($assessment->segments()->count() > 0)
                <div class="bg-white p-5 shadow-sm rounded-xl mb-6">
                    <p class="text-sm font-bold text-gray-900 mb-3">Progress Segment</p>
                    <div class="flex gap-3">
                        @foreach ($assessment->segments as $seg)
                            <div class="flex-1 rounded-xl border p-3 text-center
                                {{ $seg->isCompleted() ? 'border-emerald-200 bg-emerald-50' : ($seg->isInProgress() ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50') }}">
                                <p class="text-xs font-bold {{ $seg->isCompleted() ? 'text-emerald-600' : ($seg->isInProgress() ? 'text-indigo-600' : 'text-gray-400') }}">
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
            <div class="bg-white shadow-sm rounded-xl overflow-hidden mb-6">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                    <h3 class="text-sm font-bold text-gray-900">Detail Jawaban ({{ $assessment->answers->count() }} soal)</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($assessment->answers as $answer)
                        @php /** @var \App\Models\Question|null $question */ $question = $answer->question @endphp
                        @if (!$question) @continue @endif
                        <div class="px-5 py-4 hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $answer->is_correct ? 'bg-emerald-100 text-emerald-700' : ($answer->is_correct === false ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $answer->position }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $question->category }}</span>
                                        @php
                                            $typeColors = ['multiple_choice' => 'bg-blue-50 text-blue-700', 'essay' => 'bg-amber-50 text-amber-700', 'upload' => 'bg-purple-50 text-purple-700'];
                                            $typeLabels = ['multiple_choice' => 'MC', 'essay' => 'Essay', 'upload' => 'Upload'];
                                        @endphp
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $typeColors[$question->type] ?? '' }}">{{ $typeLabels[$question->type] ?? $question->type }}</span>
                                        @if ($isHrAssessment && $question->isMultipleChoice())
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-700">Nilai: {{ number_format($question->pointValue(), 2) }}</span>
                                        @endif

                                        @if ($question->isMultipleChoice() && $answer->is_correct !== null)
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
                                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">Skor: {{ number_format($answer->score, 0) }}</span>
                                        @elseif ($question->isEssay() || $question->isUpload())
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-600">Belum dinilai</span>
                                        @endif
                                    </div>

                                    <p class="whitespace-pre-line text-sm font-medium text-gray-900">{{ $question->text }}</p>

                                    @if ($question->image)
                                        <img src="{{ $question->imageUrl() }}" alt="Gambar soal" class="mt-2 max-h-40 rounded-xl border border-gray-200 object-contain">
                                    @endif

                                    @if ($question->isMultipleChoice())
                                        <div class="mt-3 grid gap-1.5">
                                            @foreach (['a', 'b', 'c', 'd'] as $option)
                                                @php
                                                    $optText = $question->optionText($option);
                                                    $isSelected = $answer->selected_option === $option;
                                                    $isCorrect = $question->correct_option === $option;
                                                @endphp
                                                @if ($optText)
                                                    <div class="flex items-center gap-3 rounded-lg border px-3 py-2 text-sm
                                                        {{ $isSelected && $isCorrect ? 'border-emerald-300 bg-emerald-50' : '' }}
                                                        {{ $isSelected && !$isCorrect ? 'border-rose-300 bg-rose-50' : '' }}
                                                        {{ !$isSelected && $isCorrect ? 'border-emerald-200 bg-emerald-50/50' : '' }}
                                                        {{ !$isSelected && !$isCorrect ? 'border-gray-200' : '' }}">
                                                        <span class="font-semibold uppercase text-gray-900">{{ $option }}.</span>
                                                        <span class="flex-1 {{ $isCorrect ? 'text-emerald-700 font-medium' : 'text-gray-700' }}">{{ $optText }}</span>
                                                        @if ($isCorrect)
                                                            <span class="text-xs font-bold text-emerald-600">&#10003; Benar</span>
                                                        @endif
                                                        @if ($isSelected && !$isCorrect)
                                                            <span class="text-xs font-bold text-rose-600">Dipilih &#10007;</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @elseif ($question->isEssay())
                                        <div class="mt-3 rounded-xl bg-gray-50 border border-gray-200 p-3">
                                            <p class="text-xs font-semibold text-gray-500 mb-0.5">Jawaban Peserta:</p>
                                            <p class="whitespace-pre-line text-sm text-gray-900">{{ $answer->answer_text ?? '-' }}</p>
                                        </div>
                                    @elseif ($question->isUpload())
                                        <div class="mt-3 rounded-xl bg-gray-50 border border-gray-200 p-3">
                                            <p class="text-xs font-semibold text-gray-500 mb-0.5">File Upload:</p>
                                            @if ($answer->file_path)
                                                @if (str_ends_with(strtolower($answer->file_path), '.pdf'))
                                                    <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800">Lihat PDF &#8599;</a>
                                                @elseif (in_array(strtolower(pathinfo($answer->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                    <img src="{{ route('files.show', $answer->file_path) }}" alt="Upload" class="mt-1 max-w-xs rounded-xl border border-gray-200">
                                                @else
                                                    <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-800">Download File &#8599;</a>
                                                @endif
                                            @else
                                                <p class="text-sm text-gray-400 italic">Tidak ada file</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($answer->review_notes)
                                        <div class="mt-2 flex items-start gap-1.5 text-xs text-gray-500">
                                            <svg class="h-3.5 w-3.5 shrink-0 mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                            <span>Catatan reviewer: {{ $answer->review_notes }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Back --}}
            <div class="text-center mb-4">
                <a href="{{ route('admin.assessments.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-black hover:shadow-md hover:-translate-y-0.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar Assessment
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
