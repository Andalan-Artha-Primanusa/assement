<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ActivityLog;
use App\Models\AssessmentAnswer;
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
        $assessments = Assessment::with('user')
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
                    default => null,
                };
            })
            ->when($request->filled('package'), function ($query) use ($request): void {
                $query->where('question_package_id', $request->integer('package'));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $packages = \App\Models\QuestionPackage::orderBy('name')->get();

        return view('admin.assessments.index', compact('assessments', 'packages'));
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

        $limit = min((int) config('assessment.question_limit', 10), $activeQuestionCount);
        $durationMinutes = $request->user()->assessmentDurationMinutes();

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

    public function show(Request $request, Assessment $assessment): View|RedirectResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if ($assessment->isSubmitted()) {
            return redirect()->route('assessment.result', $assessment);
        }

        if ($assessment->isExpired()) {
            app(AssessmentSecurity::class)->finishAssessment($assessment, [], true);

            return redirect()->route('assessment.result', $assessment)
                ->with('status', 'Waktu pengerjaan sudah habis. Assessment otomatis dikirim.');
        }

        if ($assessment->isBlocked()) {
            return view('assessment.blocked', compact('assessment'));
        }

        $assessment->load('answers.question');

        return view('assessment.show', compact('assessment'));
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

        $validated = $request->validate([
            'answers' => ['array'],
            'answers.*' => ['nullable', 'in:a,b,c,d'],
        ]);

        if ($assessment->isExpired()) {
            app(AssessmentSecurity::class)->finishAssessment($assessment, $validated['answers'] ?? [], true);

            return redirect()->route('assessment.result', $assessment)
                ->with('status', 'Waktu pengerjaan sudah habis. Assessment otomatis dikirim.');
        }

        app(AssessmentSecurity::class)->finishAssessment($assessment, $validated['answers'] ?? []);

        $this->notifyAdmins($assessment);

        ActivityLog::log('assessment_submit', 'Menyelesaikan assessment', Assessment::class, $assessment->id);

        return redirect()->route('assessment.result', $assessment);
    }

    public function result(Request $request, Assessment $assessment): View
    {
        $this->authorizeAssessment($request, $assessment);

        $assessment->load('user', 'questionPackage', 'answers.question');

        return view('assessment.result', compact('assessment'));
    }

    public function securityViolation(Request $request, Assessment $assessment): JsonResponse
    {
        $this->authorizeAssessment($request, $assessment);

        if (! $request->user()->is_admin && ! $assessment->isSubmitted()) {
            $data = $request->validate([
                'reason' => ['nullable', 'string', 'max:255'],
                'answers' => ['array'],
                'answers.*' => ['nullable', 'in:a,b,c,d'],
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
        abort_unless($request->user()->is_admin, 403);

        $assessment->update([
            'unlocked_at' => now(),
        ]);

        ActivityLog::log('assessment_unblock', 'Membuka blokir assessment #'.$assessment->id, Assessment::class, $assessment->id);

        return back()->with('status', 'Akses assessment berhasil dibuka kembali.');
    }

    public function extend(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $data = $request->validate([
            'extra_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        $newEndsAt = $assessment->ends_at
            ? $assessment->ends_at->addMinutes((int) $data['extra_minutes'])
            : now()->addMinutes((int) $data['extra_minutes']);

        $assessment->update([
            'ends_at' => $newEndsAt,
            'duration_minutes' => ($assessment->duration_minutes ?? 0) + (int) $data['extra_minutes'],
        ]);

        ActivityLog::log('assessment_extend', 'Memperpanjang assessment #'.$assessment->id.' +'.$data['extra_minutes'].' menit', Assessment::class, $assessment->id);

        return back()->with('status', 'Waktu assessment berhasil diperpanjang '.$data['extra_minutes'].' menit.');
    }

    private function notifyAdmins(Assessment $assessment): void
    {
        try {
            $assessment->loadMissing('user');
            $admins = User::where('is_admin', true)->get();

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
            $request->user()->is_admin || $assessment->user_id === $request->user()->id,
            403
        );
    }
}
