<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ActivityLog;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentSegment;
use App\Models\Question;
use App\Models\User;
use App\Services\AssessmentSecurity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssessmentController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $selectedType = $request->string('type')->toString();
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, ['mekanik', 'operator', 'she'])) {
            $visibleTypes = [$selectedType];
        }

        $assessments = Assessment::with('user', 'questionPackage')
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->whereHas('questionPackage', function ($q) use ($visibleTypes): void {
                    $q->whereIn('type', $visibleTypes);
                });
            })
            ->when($adminUser->isSuperAdmin() && $selectedType, function ($query) use ($selectedType): void {
                $query->whereHas('questionPackage', function ($q) use ($selectedType): void {
                    $q->where('type', $selectedType);
                });
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->whereHas('user', function ($q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'submitted' => $query->whereNotNull('submitted_at'),
                    'pending' => $query->whereNull('submitted_at')->whereNull('blocked_at'),
                    'blocked' => $query->whereNotNull('blocked_at')->whereNull('submitted_at'),
                    'pending_review' => $query->where('status', Assessment::STATUS_PENDING_REVIEW),
                    'graded' => $query->where('status', Assessment::STATUS_GRADED),
                    default => null,
                };
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $query->where('question_package_id', $request->integer('package'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $packages = \App\Models\QuestionPackage::whereIn('type', $visibleTypes)
            ->orderBy('name')
            ->get();

        return view('admin.assessments.index', compact('assessments', 'packages', 'selectedType'));
    }

    public function export(Request $request): StreamedResponse
    {
        $assessments = Assessment::with('user', 'questionPackage')
            ->when($request->filled('status'), function ($query) use ($request): void {
                match ($request->string('status')->toString()) {
                    'submitted' => $query->whereNotNull('submitted_at'),
                    'pending' => $query->whereNull('submitted_at')->whereNull('blocked_at'),
                    'blocked' => $query->whereNotNull('blocked_at')->whereNull('submitted_at'),
                    default => null,
                };
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $query->where('question_package_id', $request->integer('package'));
            })
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="assessment-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($assessments): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Peserta', 'Email', 'Paket', 'Mulai', 'Selesai',
                'Benar', 'Total', 'Nilai', 'Status', 'Pelanggaran',
            ]);

            foreach ($assessments as $a) {
                $status = $a->isSubmitted() ? 'Selesai' : ($a->isBlocked() ? 'Terblokir' : 'Berjalan');
                fputcsv($output, [
                    $a->user->name,
                    $a->user->email,
                    $a->questionPackage?->name ?? '-',
                    $a->started_at?->format('d/m/Y H:i'),
                    $a->submitted_at?->format('d/m/Y H:i'),
                    $a->correct_answers ?? 0,
                    $a->total_questions ?? 0,
                    $a->score ? number_format($a->score, 2) : '-',
                    $status,
                    $a->security_violations ?? 0,
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function start(Request $request): RedirectResponse
    {
        if (! $request->user()->canAccessAssessment()) {
            return back()->with('status', 'Masa akses assessment untuk akun ini sudah habis. Hubungi admin.');
        }

        $maxAttempts = $request->user()->max_attempts ?? (int) config('assessment.max_attempts', 1);
        $completedCount = $request->user()->assessments()->whereNotNull('submitted_at')->count();

        if ($completedCount >= $maxAttempts) {
            return back()->with('status', 'Batas maksimal percobaan assessment ('.$maxAttempts.' kali) sudah terpakai semua. Hubungi admin.');
        }

        $openAssessment = $request->user()
            ->assessments()
            ->whereNull('submitted_at')
            ->latest()
            ->first();

        if ($openAssessment) {
            return redirect()->route('assessment.show', $openAssessment);
        }

        $package = $request->user()->questionPackage;

        if ($package && ! $package->is_active) {
            return back()->with('status', 'Paket soal untuk akun ini sedang nonaktif. Hubungi admin.');
        }

        $questionQuery = Question::query()->where('is_active', true);

        if ($package) {
            $questionQuery->where('question_package_id', $package->id);
        }

        $activeQuestionCount = (clone $questionQuery)->count();

        if ($activeQuestionCount === 0) {
            $message = $package
                ? 'Paket soal '.$package->name.' belum punya soal aktif. Admin perlu menambahkan atau mengaktifkan soal.'
                : 'Belum ada soal aktif. Admin perlu menambahkan atau mengaktifkan soal.';

            return back()->with('status', $message);
        }

        $user = $request->user();
        $hasSegments = $package && $package->has_segments && ! empty($user->segment_config);

        if ($hasSegments) {
            return $this->startSegmentedAssessment($request, $package, $questionQuery, $activeQuestionCount);
        }

        $limit = $activeQuestionCount;
        $durationMinutes = $user->assessmentDurationMinutes();

        $assessment = DB::transaction(function () use ($request, $limit, $durationMinutes, $package, $questionQuery) {
            $assessment = Assessment::create([
                'user_id' => $request->user()->id,
                'question_package_id' => $package?->id,
                'total_questions' => $limit,
                'started_at' => now(),
                'duration_minutes' => $durationMinutes,
                'ends_at' => now()->addMinutes($durationMinutes),
            ]);

            (clone $questionQuery)
                ->inRandomOrder()
                ->limit($limit)
                ->get()
                ->each(function (Question $question, int $index) use ($assessment): void {
                    AssessmentAnswer::create([
                        'assessment_id' => $assessment->id,
                        'question_id' => $question->id,
                        'position' => $index + 1,
                    ]);
                });

            return $assessment;
        });

        ActivityLog::log('assessment_start', 'Memulai assessment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.show', $assessment);
    }

    private function startSegmentedAssessment(Request $request, $package, $questionQuery, int $activeQuestionCount): RedirectResponse
    {
        $user = $request->user();
        $segmentConfig = $user->segment_config;
        $totalDuration = collect($segmentConfig)->sum('duration');

        $assessment = DB::transaction(function () use ($request, $package, $questionQuery, $activeQuestionCount, $segmentConfig, $totalDuration) {
            $assessment = Assessment::create([
                'user_id' => $request->user()->id,
                'question_package_id' => $package->id,
                'total_questions' => $activeQuestionCount,
                'started_at' => now(),
                'duration_minutes' => $totalDuration,
                'ends_at' => now()->addMinutes($totalDuration),
            ]);

            (clone $questionQuery)
                ->inRandomOrder()
                ->limit($activeQuestionCount)
                ->get()
                ->each(function (Question $question, int $index) use ($assessment): void {
                    AssessmentAnswer::create([
                        'assessment_id' => $assessment->id,
                        'question_id' => $question->id,
                        'position' => $index + 1,
                    ]);
                });

            foreach ($segmentConfig as $index => $seg) {
                AssessmentSegment::create([
                    'assessment_id' => $assessment->id,
                    'type' => $seg['type'],
                    'duration_minutes' => (int) $seg['duration'],
                    'order_index' => $index,
                    'status' => $index === 0 ? AssessmentSegment::STATUS_IN_PROGRESS : AssessmentSegment::STATUS_PENDING,
                    'started_at' => $index === 0 ? now() : null,
                ]);
            }

            return $assessment;
        });

        ActivityLog::log('assessment_start', 'Memulai assessment bersegment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.show', $assessment);
    }

    public function show(Request $request, Assessment $assessment): View|RedirectResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->isSubmitted()) {
            return redirect()->route('assessment.result', $assessment);
        }

        if ($assessment->isBlocked()) {
            return view('assessment.blocked', compact('assessment'));
        }

        if ($assessment->isExpired()) {
            app(AssessmentSecurity::class)->finishAssessment($assessment, [], true);

            return redirect()->route('assessment.result', $assessment)
                ->with('status', 'Waktu pengerjaan sudah habis. Assessment otomatis dikirim.');
        }

        $assessment->load('answers.question', 'segments');

        $hasSegments = $assessment->segments()->count() > 0;
        $currentSegment = null;
        $segmentAnswers = null;

        if ($hasSegments) {
            $currentSegment = $assessment->segments()
                ->where('status', AssessmentSegment::STATUS_IN_PROGRESS)
                ->first();

            if (! $currentSegment) {
                $lastCompleted = $assessment->segments()->orderByDesc('order_index')->first();
                if ($lastCompleted && $lastCompleted->isCompleted()) {
                    return redirect()->route('assessment.result', $assessment);
                }
                return redirect()->route('assessment.show', $assessment);
            }

            if ($currentSegment->remainingSeconds() <= 0) {
                return $this->completeSegment($request, $assessment, $currentSegment);
            }

            $segmentAnswers = $assessment->answers->filter(
                fn ($a) => $a->question->type === $currentSegment->type
            );

            return view('assessment.show-segmented', compact('assessment', 'hasSegments', 'currentSegment', 'segmentAnswers'));
        }

        $hasUploadQuestions = $assessment->answers->contains(fn ($answer) => $answer->question->isUpload());

        return view('assessment.show', compact('assessment', 'hasUploadQuestions'));
    }

    private function completeSegment(Request $request, Assessment $assessment, AssessmentSegment $segment): RedirectResponse
    {
        $segment->update([
            'status' => AssessmentSegment::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $nextSegment = $assessment->segments()
            ->where('status', AssessmentSegment::STATUS_PENDING)
            ->orderBy('order_index')
            ->first();

        if ($nextSegment) {
            $nextSegment->update([
                'status' => AssessmentSegment::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);

            ActivityLog::log('assessment_segment_next', 'Lanjut ke segment '.$nextSegment->type, Assessment::class, $assessment->id);

            return redirect()->route('assessment.show', $assessment);
        }

        app(AssessmentSecurity::class)->finishAssessment($assessment, [], true);

        $this->notifyAdmins($assessment);

        ActivityLog::log('assessment_submit', 'Semua segment selesai, assessment otomatis dikirim', Assessment::class, $assessment->id);

        return redirect()->route('assessment.result', $assessment)
            ->with('status', 'Semua segment selesai. Assessment otomatis dikirim.');
    }

    public function submit(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->isSubmitted()) {
            return redirect()->route('assessment.result', $assessment);
        }

        if ($assessment->isBlocked()) {
            return redirect()->route('assessment.show', $assessment)
                ->with('status', 'Assessment terkunci. Minta admin untuk membuka akses.');
        }

        $hasSegments = $assessment->segments()->count() > 0;

        if ($hasSegments) {
            $currentSegment = $assessment->segments()
                ->where('status', AssessmentSegment::STATUS_IN_PROGRESS)
                ->first();

            if ($currentSegment) {
                $this->saveSegmentAnswers($request, $assessment, $currentSegment);

                return $this->completeSegment($request, $assessment, $currentSegment);
            }
        }

        $validationRules = [
            'answers' => ['array'],
        ];

        $assessment->load('answers.question');
        foreach ($assessment->answers as $answer) {
            if ($answer->question->isEssay()) {
                $validationRules['answers.'.$answer->id] = ['nullable', 'string', 'max:5000'];
            } elseif ($answer->question->isUpload()) {
                $validationRules['answers.'.$answer->id] = ['nullable', 'file', 'max:10240'];
            } else {
                $validationRules['answers.'.$answer->id] = ['nullable', 'in:a,b,c,d'];
            }
        }

        $validated = $request->validate($validationRules);

        if ($assessment->isExpired()) {
            $this->processUploadedFiles($request, $assessment, $validated['answers'] ?? []);
            app(AssessmentSecurity::class)->finishAssessment($assessment, $validated['answers'] ?? [], true);

            return redirect()->route('assessment.result', $assessment)
                ->with('status', 'Waktu pengerjaan sudah habis. Assessment otomatis dikirim.');
        }

        $this->processUploadedFiles($request, $assessment, $validated['answers'] ?? []);
        app(AssessmentSecurity::class)->finishAssessment($assessment, $validated['answers'] ?? []);

        $this->notifyAdmins($assessment);

        ActivityLog::log('assessment_submit', 'Menyelesaikan assessment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.result', $assessment);
    }

    private function saveSegmentAnswers(Request $request, Assessment $assessment, AssessmentSegment $segment): void
    {
        $segmentAnswers = $assessment->answers->filter(
            fn ($a) => $a->question->type === $segment->type
        );

        foreach ($segmentAnswers as $answer) {
            if ($answer->question->isEssay()) {
                $text = $request->input('answers.'.$answer->id);
                if ($text !== null) {
                    $answer->update(['answer_text' => $text]);
                }
            } elseif ($answer->question->isUpload()) {
                if ($request->hasFile('answers.'.$answer->id)) {
                    $this->processUploadedFiles($request, $assessment, [$answer->id => $request->file('answers.'.$answer->id)]);
                }
            } else {
                $value = $request->input('answers.'.$answer->id);
                if ($value !== null && in_array($value, ['a', 'b', 'c', 'd'])) {
                    $answer->update([
                        'selected_option' => $value,
                        'is_correct' => $value === $answer->question->correct_option,
                    ]);
                }
            }
        }
    }

    public function result(Request $request, Assessment $assessment): View
    {
        $this->authorizeAssessment($request, $assessment);

        $assessment->load('user', 'questionPackage', 'segments', 'answers.question');

        return view('assessment.result', compact('assessment'));
    }

    public function certificate(Request $request, Assessment $assessment): View
    {
        $this->authorizeAssessment($request, $assessment);

        abort_unless($assessment->isSubmitted(), 404);

        $assessment->load('user', 'questionPackage');
        $package = $assessment->questionPackage;

        abort_unless($package && $package->is_certificate, 404);

        $grade = $package->getGrade((float) $assessment->score);
        abort_unless(in_array($grade, ['Lolos', 'Dipertimbangkan']), 403);

        $certificateNumber = 'AA-PRA/'.$assessment->submitted_at->format('Y/m').'/'.$assessment->id.'-'.$assessment->user_id;

        return view('assessment.certificate', compact('assessment', 'package', 'grade', 'certificateNumber'));
    }

    public function securityViolation(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if (! $request->user()->isAdmin() && ! $assessment->isSubmitted()) {
            $data = $request->validate([
                'reason' => ['nullable', 'string', 'max:255'],
                'answers' => ['array'],
            ]);

            if (! $assessment->isBlocked()) {
                $reason = $data['reason'] ?? 'Peserta meninggalkan halaman assessment.';
                $status = app(AssessmentSecurity::class)->recordViolation($assessment, $reason, $data['answers'] ?? []);

                if ($status['submitted']) {
                    return response()->json([
                        ...$status,
                        'redirect' => route('assessment.result', $assessment),
                    ]);
                }
            }
        }

        $assessment->refresh();

        return response()->json([
            'blocked' => $assessment->isBlocked(),
            'submitted' => $assessment->isSubmitted(),
            'violations' => $assessment->security_violations,
        ]);
    }

    public function unblock(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $assessment->update([
            'unlocked_at' => now(),
            'blocked_at' => null,
            'block_reason' => null,
            'security_violations' => 0,
        ]);

        ActivityLog::log('assessment_unblock', 'Membuka blokir assessment #'.$assessment->id, Assessment::class, $assessment->id);

        return back()->with('status', 'Akses assessment berhasil dibuka kembali.');
    }

    public function adminQuestions(Request $request, Assessment $assessment): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $assessment->load(['answers.question', 'user', 'questionPackage', 'segments']);

        return view('admin.assessments.questions', compact('assessment'));
    }

    public function setDuration(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $newEndsAt = $assessment->started_at
            ? $assessment->started_at->addMinutes((int) $data['duration_minutes'])
            : now()->addMinutes((int) $data['duration_minutes']);

        $assessment->update([
            'duration_minutes' => (int) $data['duration_minutes'],
            'ends_at' => $newEndsAt,
        ]);

        ActivityLog::log('assessment_set_duration', 'Set durasi assessment #'.$assessment->id.' ke '.$data['duration_minutes'].' menit', Assessment::class, $assessment->id);

        return back()->with('status', 'Durasi assessment berhasil diatur ke '.$data['duration_minutes'].' menit.');
    }

    private function processUploadedFiles(Request $request, Assessment $assessment, array $answers): void
    {
        $assessment->load('answers.question');

        foreach ($assessment->answers as $answer) {
            if ($answer->question && $answer->question->isUpload() && $request->hasFile('answers.'.$answer->id)) {
                $file = $request->file('answers.'.$answer->id);
                $path = $file->store('assessment-uploads', 'public');
                $answer->update(['file_path' => $path]);
            }
        }
    }

    private function notifyAdmins(Assessment $assessment): void
    {
        try {
            $assessment->loadMissing('user');
            $admins = User::whereIn('role', [User::ROLE_ADMIN_MEKANIK, User::ROLE_ADMIN_OPERATION, User::ROLE_ADMIN_SHE])->get();

            foreach ($admins as $admin) {
                Mail::send('emails.assessment-completed', [
                    'assessment' => $assessment,
                    'user' => $assessment->user,
                    'resultUrl' => route('assessment.result', $assessment),
                ], function ($message) use ($admin, $assessment): void {
                    $message->to($admin->email, $admin->name)
                        ->subject('Assessment Selesai: '.$assessment->user->name);
                });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi admin: '.$e->getMessage());
        }
    }

    private function authorizeAssessment(Request $request, Assessment $assessment): void
    {
        abort_unless(
            $request->user()->isAdmin() || $assessment->user_id === $request->user()->id,
            403
        );
    }
}
