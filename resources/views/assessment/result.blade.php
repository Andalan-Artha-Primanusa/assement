<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Hasil Assessment</h2>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Kembali ke dashboard</a>
        </div>
    </x-slot>

    @php
        $package = $assessment->questionPackage;
        $hasFinalScore = $assessment->isSubmitted() && ! $assessment->isPendingReview();
        $grade = $hasFinalScore && $package ? $package->getGrade((float) $assessment->score) : null;
        $showCertificate = $package && $package->is_certificate && $grade && in_array($grade, ['Lolos', 'Dipertimbangkan']);

        $allAnswers = $assessment->answers;
        $mcAnswers = $allAnswers->filter(fn($a) => $a->question && $a->question->isMultipleChoice());
        $essayAnswers = $allAnswers->filter(fn($a) => $a->question && $a->question->isEssay());
        $uploadAnswers = $allAnswers->filter(fn($a) => $a->question && $a->question->isUpload());
        $nonMcAnswers = $allAnswers->filter(fn($a) => $a->question && in_array($a->question->type, ['essay', 'upload']));

        $mcScore = $mcAnswers->count() > 0
            ? round($mcAnswers->where('is_correct', true)->count() / $mcAnswers->count() * 100, 2)
            : null;

        $hasEssay = $essayAnswers->isNotEmpty();
        $hasUpload = $uploadAnswers->isNotEmpty();
        $hasNonMc = $nonMcAnswers->isNotEmpty();

        $essayGraded = $hasEssay && $essayAnswers->every(fn($a) => $a->score !== null);
        $essayScore = $essayGraded ? round($essayAnswers->avg('score'), 2) : null;

        $uploadGraded = $hasUpload && $uploadAnswers->every(fn($a) => $a->score !== null);
        $uploadScore = $uploadGraded ? round($uploadAnswers->avg('score'), 2) : null;

        $nonMcGraded = $hasNonMc && $nonMcAnswers->every(fn($a) => $a->score !== null);
        $nonMcScore = $nonMcGraded ? round($nonMcAnswers->avg('score'), 2) : null;

        $finalScore = $hasFinalScore
            ? (float) $assessment->score
            : ($hasNonMc ? null : $mcScore);

        $passingGrade = $package?->min_score_lolos ?? 75;
        $isPassed = $finalScore !== null && $finalScore >= $passingGrade;

        $latestReview = $nonMcAnswers->filter(fn($a) => $a->score !== null)->sortByDesc('reviewed_at')->first();
        $feedbacks = $nonMcAnswers->filter(fn($a) => $a->review_notes)->pluck('review_notes')->implode("\n");
    @endphp

    @push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body, .app-layout *, [class*="font-"] { font-family: 'Poppins', sans-serif !important; }

        .page-bg { background: #F8FAFC; min-height: 100vh; }

        .kpi-card {
            background: #fff;
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(20px);
        }
        .kpi-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.08), 0 4px 10px rgba(0,0,0,0.04);
            transform: translateY(-2px);
        }
        .kpi-card.visible { opacity: 1; transform: translateY(0); }

        .score-card {
            background: #fff;
            border-radius: 18px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        }

        .circle-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .circle-svg { transform: rotate(-90deg); }
        .circle-track { fill: none; stroke: #E5E7EB; }
        .circle-progress {
            fill: none;
            stroke-linecap: round;
            transition: stroke-dashoffset 1.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .circle-score {
            position: absolute;
            font-weight: 700;
            line-height: 1;
        }
        .circle-label {
            text-align: center;
            margin-top: 1rem;
            font-weight: 600;
            color: #374151;
        }

        .badge-pass {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.5rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.025em;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .badge-pass:hover { transform: scale(1.03); }
        .badge-pass.passed {
            background: linear-gradient(135deg, #16A34A, #22C55E);
            color: #fff;
        }
        .badge-pass.failed {
            background: linear-gradient(135deg, #DC2626, #EF4444);
            color: #fff;
        }
        .badge-pass.waiting {
            background: linear-gradient(135deg, #D97706, #F59E0B);
            color: #fff;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.875rem 0;
            border-bottom: 1px solid #F3F4F6;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6B7280; font-weight: 500; font-size: 0.875rem; }
        .detail-value { font-weight: 700; color: #111827; font-size: 1rem; }

        .summary-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .status-dot {
            width: 0.625rem;
            height: 0.625rem;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.animate-pulse { animation: dotPulse 2s ease-in-out infinite; }
        @keyframes dotPulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.6s ease-out forwards; }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
    </style>
    @endpush

    <div class="page-bg py-8 sm:py-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Alerts --}}
            @if ($assessment->auto_submitted_at)
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 flex items-center gap-3">
                    <div class="summary-icon bg-amber-100">
                        <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold">Assessment otomatis selesai</p>
                        <p class="text-amber-700 text-xs mt-0.5">Pada {{ $assessment->auto_submitted_at->format('d M Y H:i') }} karena pelanggaran melebihi batas.</p>
                    </div>
                </div>
            @endif

            @if ($assessment->isPendingReview())
                <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-800 flex items-center gap-3">
                    <div class="summary-icon bg-blue-100">
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold">Menunggu Review SHE</p>
                        <p class="text-blue-700 text-xs mt-0.5">Essay/Upload sedang dinilai admin SHE. Nilai akhir akan diperbarui setelah review selesai.</p>
                    </div>
                </div>
            @endif

            {{-- Summary Cards --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="kpi-card">
                    <div class="flex items-center gap-3">
                        <div class="summary-icon bg-indigo-50">
                            <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Peserta</p>
                            <p class="mt-1 text-sm font-bold text-gray-900 truncate">{{ $assessment->user->name }}</p>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="flex items-center gap-3">
                        <div class="summary-icon bg-violet-50">
                            <svg class="h-5 w-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Paket Soal</p>
                            <p class="mt-1 text-sm font-bold text-gray-900 truncate">{{ $package?->name ?? 'Semua paket' }}</p>
                            @if ($package)
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ \App\Models\QuestionPackage::typeLabel($package->type) }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="flex items-center gap-3">
                        <div class="summary-icon bg-emerald-50">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Jawaban Benar</p>
                            <p class="mt-1 text-2xl font-bold text-emerald-600">{{ $assessment->correct_answers }}<span class="text-sm font-medium text-gray-400">/{{ $assessment->total_questions }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="flex items-center gap-3">
                        <div class="summary-icon bg-amber-50">
                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Nilai Akhir</p>
                            @if ($hasFinalScore)
                                <p class="mt-1 text-2xl font-bold text-amber-600">{{ number_format($assessment->score, 2) }}</p>
                            @elseif ($assessment->isPendingReview())
                                <span class="mt-2 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Menunggu Review SHE</span>
                            @else
                                <p class="mt-1 text-2xl font-bold text-gray-300">--</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Score Breakdown --}}
            <div class="score-card mb-6 animate-fade-in delay-2">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Assessment Score Breakdown</h3>
                <p class="text-sm text-gray-400 mb-8">Rincian skor penilaian Anda</p>

                {{-- Circular Progress Cards --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8 mb-8">

                    {{-- Multiple Choice --}}
                    <div class="flex flex-col items-center">
                        <div class="circle-wrapper" data-size="120">
                            <svg class="circle-svg" width="120" height="120" viewBox="0 0 120 120">
                                <circle class="circle-track" cx="60" cy="60" r="52" stroke-width="8"/>
                                <circle class="circle-progress" cx="60" cy="60" r="52" stroke-width="8"
                                    stroke="#2563EB"
                                    stroke-dasharray="326.73"
                                    stroke-dashoffset="326.73"
                                    data-target="{{ $mcScore !== null ? $mcScore : 0 }}"/>
                            </svg>
                            <span class="circle-score text-xl" style="color: #2563EB">
                                @if ($mcScore !== null)
                                    <span class="counter" data-target="{{ $mcScore }}">0</span>
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                        <p class="circle-label text-sm">Multiple Choice</p>
                        @if ($mcScore !== null)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $mcAnswers->where('is_correct', true)->count() }}/{{ $mcAnswers->count() }} benar</p>
                        @endif
                    </div>

                    {{-- Essay --}}
                    <div class="flex flex-col items-center">
                        <div class="circle-wrapper" data-size="120">
                            <svg class="circle-svg" width="120" height="120" viewBox="0 0 120 120">
                                <circle class="circle-track" cx="60" cy="60" r="52" stroke-width="8"/>
                                <circle class="circle-progress" cx="60" cy="60" r="52" stroke-width="8"
                                    stroke="{{ $essayScore !== null ? '#F59E0B' : '#D97706' }}"
                                    stroke-dasharray="326.73"
                                    stroke-dashoffset="326.73"
                                    data-target="{{ $essayScore !== null ? $essayScore : 0 }}"/>
                            </svg>
                            <span class="circle-score text-xl" style="color: {{ $essayScore !== null ? '#F59E0B' : '#D97706' }}">
                                @if ($essayScore !== null)
                                    <span class="counter" data-target="{{ $essayScore }}">0</span>
                                @elseif ($hasEssay)
                                    <span class="text-lg" style="color: #D97706">
                                        <svg class="h-5 w-5 mx-auto animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke="currentColor" opacity="0.25"/><path stroke-width="2" stroke-linecap="round" d="M4 12a8 8 0 018-8" stroke="currentColor"/></svg>
                                    </span>
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                        <p class="circle-label text-sm">Essay</p>
                        @if ($hasEssay && $essayScore === null)
                            <p class="text-xs mt-0.5 font-semibold" style="color: #D97706">
                                <span class="inline-block animate-pulse">&#9203;</span> Waiting HR Review
                            </p>
                        @elseif ($essayScore !== null)
                            <p class="text-xs text-gray-400 mt-0.5">Skor rata-rata</p>
                        @else
                            <p class="text-xs text-gray-300 mt-0.5">Tidak ada</p>
                        @endif
                    </div>

                    {{-- Portfolio (Upload) --}}
                    <div class="flex flex-col items-center">
                        <div class="circle-wrapper" data-size="120">
                            <svg class="circle-svg" width="120" height="120" viewBox="0 0 120 120">
                                <circle class="circle-track" cx="60" cy="60" r="52" stroke-width="8"/>
                                <circle class="circle-progress" cx="60" cy="60" r="52" stroke-width="8"
                                    stroke="{{ $uploadScore !== null ? '#8B5CF6' : '#7C3AED' }}"
                                    stroke-dasharray="326.73"
                                    stroke-dashoffset="326.73"
                                    data-target="{{ $uploadScore !== null ? $uploadScore : 0 }}"/>
                            </svg>
                            <span class="circle-score text-xl" style="color: {{ $uploadScore !== null ? '#8B5CF6' : '#7C3AED' }}">
                                @if ($uploadScore !== null)
                                    <span class="counter" data-target="{{ $uploadScore }}">0</span>
                                @elseif ($hasUpload)
                                    <span class="text-lg" style="color: #7C3AED">
                                        <svg class="h-5 w-5 mx-auto animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke="currentColor" opacity="0.25"/><path stroke-width="2" stroke-linecap="round" d="M4 12a8 8 0 018-8" stroke="currentColor"/></svg>
                                    </span>
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                        <p class="circle-label text-sm">Portfolio</p>
                        @if ($hasUpload && $uploadScore === null)
                            <p class="text-xs mt-0.5 font-semibold" style="color: #7C3AED">
                                <span class="inline-block animate-pulse">&#9203;</span> Waiting HR Review
                            </p>
                        @elseif ($uploadScore !== null)
                            <p class="text-xs text-gray-400 mt-0.5">Skor rata-rata</p>
                        @else
                            <p class="text-xs text-gray-300 mt-0.5">Tidak ada</p>
                        @endif
                    </div>

                    {{-- Final Score --}}
                    <div class="flex flex-col items-center">
                        <div class="circle-wrapper" data-size="140">
                            <svg class="circle-svg" width="140" height="140" viewBox="0 0 140 140">
                                <circle class="circle-track" cx="70" cy="70" r="60" stroke-width="10"/>
                                <circle class="circle-progress" cx="70" cy="70" r="60" stroke-width="10"
                                    stroke="{{ $finalScore !== null ? ($isPassed ? '#16A34A' : '#DC2626') : '#9CA3AF' }}"
                                    stroke-dasharray="376.99"
                                    stroke-dashoffset="376.99"
                                    data-target="{{ $finalScore !== null ? $finalScore : 0 }}"/>
                            </svg>
                            <span class="circle-score text-2xl" style="color: {{ $finalScore !== null ? ($isPassed ? '#16A34A' : '#DC2626') : '#9CA3AF' }}">
                                @if ($finalScore !== null)
                                    <span class="counter" data-target="{{ $finalScore }}">0</span>
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                        <p class="circle-label text-sm font-bold">Final Score</p>
                    </div>
                </div>

                {{-- Passing Grade & Status --}}
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-6 border-t border-gray-100">
                    <div class="text-center sm:text-right">
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">Passing Grade</p>
                        <p class="text-2xl font-bold text-gray-900 mt-0.5">{{ $passingGrade }}</p>
                    </div>

                    <div class="hidden sm:block w-px h-12 bg-gray-200"></div>

                    <div class="text-center sm:text-left">
                        <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-2">Assessment Status</p>
                        @if ($finalScore !== null)
                            @if ($isPassed)
                                <span class="badge-pass passed">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    PASSED
                                </span>
                            @else
                                <span class="badge-pass failed">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    FAILED
                                </span>
                            @endif
                        @else
                            <span class="badge-pass waiting">
                                <svg class="h-5 w-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke="currentColor" opacity="0.25"/><path stroke-width="2" stroke-linecap="round" d="M4 12a8 8 0 018-8" stroke="currentColor"/></svg>
                                Waiting for Essay Review
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Feedback HR --}}
                @if ($feedbacks)
                    <div class="mt-6 rounded-xl bg-gray-50 border border-gray-100 p-5">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Feedback HR</p>
                        </div>
                        <p class="whitespace-pre-line text-sm text-gray-700 italic leading-relaxed">"{{ $feedbacks }}"</p>
                    </div>
                @endif

                {{-- Date --}}
                @if ($latestReview && $latestReview->reviewed_at)
                    <div class="mt-4 flex items-center gap-2 text-xs text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Tanggal Penilaian: <strong class="text-gray-600">{{ $latestReview->reviewed_at->format('d M Y H:i') }} WIB</strong></span>
                    </div>
                @endif
            </div>

            {{-- Assessment Details --}}
            <div class="score-card mb-6 animate-fade-in delay-3">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Assessment Details</h3>
                <div class="divide-y divide-gray-50">
                    <div class="detail-row">
                        <span class="detail-label">Multiple Choice Score</span>
                        <span class="detail-value" style="color: #2563EB">{{ $mcScore !== null ? number_format($mcScore, 2) : '--' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Essay Score</span>
                        @if ($essayScore !== null)
                            <span class="detail-value" style="color: #F59E0B">{{ number_format($essayScore, 2) }}</span>
                        @elseif ($hasEssay)
                            <span class="text-sm font-semibold" style="color: #D97706">&#9203; Menunggu</span>
                        @else
                            <span class="detail-value text-gray-300">--</span>
                        @endif
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Portfolio Score</span>
                        @if ($uploadScore !== null)
                            <span class="detail-value" style="color: #8B5CF6">{{ number_format($uploadScore, 2) }}</span>
                        @elseif ($hasUpload)
                            <span class="text-sm font-semibold" style="color: #7C3AED">&#9203; Menunggu</span>
                        @else
                            <span class="detail-value text-gray-300">--</span>
                        @endif
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Final Score</span>
                        @if ($finalScore !== null)
                            <span class="detail-value" style="color: {{ $isPassed ? '#16A34A' : '#DC2626' }}">{{ number_format($finalScore, 2) }}</span>
                        @else
                            <span class="detail-value text-gray-300">-</span>
                        @endif
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Passing Grade</span>
                        <span class="detail-value text-gray-900">{{ $passingGrade }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tanggal Selesai</span>
                        <span class="detail-value text-gray-900">{{ $assessment->submitted_at?->format('d M Y H:i') ?? '--' }}</span>
                    </div>
                </div>
            </div>

            {{-- Certificate --}}
            @if ($showCertificate && $hasFinalScore)
                <div class="score-card mb-6 text-center animate-fade-in delay-4">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 shadow-lg shadow-indigo-200">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Selamat! Anda Berhasil</h3>
                    <p class="mt-1 text-sm text-gray-500">Anda berhak mendapatkan sertifikat kelulusan assessment.</p>
                    <a href="{{ route('assessment.certificate', $assessment) }}" target="_blank"
                       class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-md shadow-indigo-200 transition-all hover:from-indigo-700 hover:to-blue-700 hover:shadow-lg hover:-translate-y-0.5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Lihat Sertifikat
                    </a>
                </div>
            @endif

            {{-- Segment Progress --}}
            @if ($assessment->segments()->count() > 0)
                <div class="score-card mb-6">
                    <p class="text-sm font-bold text-gray-900 mb-3">Progress Segment</p>
                    <div class="flex gap-3">
                        @foreach ($assessment->segments as $seg)
                            <div class="flex-1 rounded-xl border p-3 text-center transition-all
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
            @if ($assessment->answers->count() > 0)
                <div class="score-card mb-6 overflow-hidden p-0">
                    <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-180')"
                        class="w-full flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors">
                        <h3 class="text-lg font-bold text-gray-900">Detail Jawaban</h3>
                        <svg class="chevron h-5 w-5 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="hidden">
                        <div class="divide-y divide-gray-100">
                            @foreach ($assessment->answers as $answer)
                                @php($question = $answer->question)
                                @if (!$question) @continue @endif
                                <div class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                            {{ $answer->is_correct ? 'bg-emerald-100 text-emerald-700' : ($answer->is_correct === false ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600') }}">
                                            {{ $answer->position }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                @if ($question->isMultipleChoice())
                                                    <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">PG</span>
                                                @elseif ($question->isEssay())
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">Essay</span>
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
                                                <img src="{{ route('files.show', $question->image) }}" alt="Gambar soal" class="mt-2 max-h-40 rounded-xl border border-gray-200 object-contain">
                                            @endif

                                            @if ($question->isMultipleChoice())
                                                <div class="mt-2 space-y-0.5">
                                                    @foreach (['a', 'b', 'c', 'd'] as $opt)
                                                        @php($optText = $question->optionText($opt))
                                                        @if ($optText)
                                                            @php($isCorrect = $question->correct_option === $opt)
                                                            @php($isSelected = $answer->selected_option === $opt)
                                                            <div class="flex items-center gap-2 text-xs rounded-lg px-3 py-1.5
                                                                {{ $isCorrect ? 'bg-emerald-50 text-emerald-700 font-semibold' : '' }}
                                                                {{ $isSelected && !$isCorrect ? 'bg-rose-50 text-rose-700' : '' }}
                                                                {{ !$isCorrect && !$isSelected ? 'text-gray-500' : '' }}">
                                                                <span class="shrink-0 font-bold uppercase">{{ $opt }}.</span>
                                                                <span class="flex-1">{{ $optText }}</span>
                                                                @if ($isCorrect && $isSelected)
                                                                    <span class="shrink-0 text-emerald-600">&#10003; Benar</span>
                                                                @elseif ($isCorrect)
                                                                    <span class="shrink-0 text-emerald-500">&#10003;</span>
                                                                @elseif ($isSelected)
                                                                    <span class="shrink-0 text-rose-600 font-bold">&#10007; Dipilih</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($question->isEssay())
                                                <div class="mt-2 p-3 rounded-xl bg-gray-50 border border-gray-200">
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

                                            @if ($question->isUpload())
                                                <div class="mt-2 p-3 rounded-xl bg-gray-50 border border-gray-200">
                                                    <p class="text-xs font-semibold text-gray-500 mb-0.5">File:</p>
                                                    @if ($answer->file_path)
                                                        @if (str_ends_with(strtolower($answer->file_path), '.pdf'))
                                                            <a href="{{ route('files.show', $answer->file_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Lihat PDF</a>
                                                        @elseif (in_array(strtolower(pathinfo($answer->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                                            <img src="{{ route('files.show', $answer->file_path) }}" alt="Upload" class="max-w-xs rounded-xl border border-gray-200">
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
                </div>
            @endif

            {{-- Back to Dashboard --}}
            <div class="text-center mt-8 mb-4">
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-8 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-black hover:shadow-md hover:-translate-y-0.5">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const CIRCLE_R = { 120: 52, 140: 60 };
            const CIRCUMFERENCE_120 = 2 * Math.PI * 52;
            const CIRCUMFERENCE_140 = 2 * Math.PI * 60;

            function animateCircles() {
                document.querySelectorAll('.circle-progress').forEach(function (circle) {
                    var target = parseFloat(circle.getAttribute('data-target')) || 0;
                    var sizeAttr = circle.closest('.circle-wrapper').getAttribute('data-size');
                    var circumference = sizeAttr === '140' ? CIRCUMFERENCE_140 : CIRCUMFERENCE_120;
                    var offset = circumference - (target / 100) * circumference;

                    circle.style.strokeDasharray = circumference;
                    circle.style.strokeDashoffset = circumference;
                    circle.getBoundingClientRect();
                    setTimeout(function () {
                        circle.style.strokeDashoffset = offset;
                    }, 100);
                });
            }

            function animateCounters() {
                document.querySelectorAll('.counter').forEach(function (el) {
                    var target = parseFloat(el.getAttribute('data-target'));
                    var duration = 1800;
                    var start = performance.now();

                    function easeOutQuart(t) {
                        return 1 - Math.pow(1 - t, 4);
                    }

                    function step(now) {
                        var elapsed = now - start;
                        var progress = Math.min(elapsed / duration, 1);
                        var value = target * easeOutQuart(progress);
                        el.textContent = value.toFixed(2);
                        if (progress < 1) requestAnimationFrame(step);
                    }
                    requestAnimationFrame(step);
                });
            }

            function animateCards() {
                document.querySelectorAll('.kpi-card').forEach(function (card, i) {
                    setTimeout(function () {
                        card.classList.add('visible');
                    }, 100 + i * 100);
                });
            }

            setTimeout(animateCircles, 300);
            setTimeout(animateCounters, 500);
            animateCards();
        });
    </script>
    @endpush
</x-app-layout>
