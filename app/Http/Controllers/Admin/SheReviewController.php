<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Question;
use App\Models\QuestionPackage;
use App\Models\User;
use App\Support\SheScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SheReviewController extends Controller
{
    public function index(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();
        $selectedType = QuestionPackage::TYPE_SHE;

        abort_unless(in_array(QuestionPackage::TYPE_SHE, $visibleTypes, true), 403);

        $assessments = Assessment::with('user', 'questionPackage', 'answers')
            ->where('status', Assessment::STATUS_PENDING_REVIEW)
            ->whereHas('questionPackage', function ($q) use ($selectedType): void {
                $q->where('type', $selectedType);
            })
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $this->applyAssessmentSiteScope($query, $adminUser);
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->whereHas('user', function ($q) use ($search): void {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.she-review.index', compact('assessments', 'selectedType'));
    }

    public function show(Assessment $assessment): View
    {
        abort_unless($assessment->submitted_at !== null, 404);

        $assessment->load('user', 'questionPackage', 'answers.question');
        abort_unless($assessment->questionPackage?->type === QuestionPackage::TYPE_SHE, 404);
        abort_unless(request()->user()->canManageType($assessment->questionPackage?->type ?? ''), 403);
        $this->authorizeAssessmentSite($assessment, request()->user());

        $answers = $assessment->answers->filter(function ($answer) {
            return $answer->question && ($answer->question->isEssay() || $answer->question->isUpload());
        });

        $selectedType = $assessment->questionPackage?->type ?? 'she';

        return view('admin.she-review.show', compact('assessment', 'answers', 'selectedType'));
    }

    public function grade(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($assessment->status === Assessment::STATUS_PENDING_REVIEW, 400);

        $assessment->load('questionPackage', 'user');
        abort_unless($assessment->questionPackage?->type === QuestionPackage::TYPE_SHE, 404);
        abort_unless($request->user()->canManageType($assessment->questionPackage?->type ?? ''), 403);
        $this->authorizeAssessmentSite($assessment, $request->user());

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes' => ['array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $assessment->load('answers.question');

        $essayUploadAnswers = $assessment->answers->filter(function ($answer) {
            return $answer->question && ($answer->question->isEssay() || $answer->question->isUpload());
        });

        $manualCount = 0;

        foreach ($essayUploadAnswers as $answer) {
            if (isset($validated['scores'][$answer->id])) {
                $answer->update([
                    'score' => $validated['scores'][$answer->id],
                    'review_notes' => $validated['notes'][$answer->id] ?? null,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                $manualCount++;
            }
        }

        if ($manualCount !== $essayUploadAnswers->count()) {
            return back()
                ->withErrors(['scores' => 'Semua jawaban Essay/Upload wajib diberi nilai sebelum assessment diselesaikan.'])
                ->withInput();
        }

        $finalScore = SheScore::calculate($assessment->answers->fresh('question'))['final'];

        $assessment->update([
            'score' => $finalScore,
            'status' => Assessment::STATUS_GRADED,
        ]);

        ActivityLog::log('she_review', 'Review SHE assessment #'.$assessment->id, Assessment::class, $assessment->id);

        $selectedType = $assessment->questionPackage?->type ?? 'she';

        return redirect()->route('admin.she-review.index', ['type' => $selectedType])
            ->with('status', 'Nilai assessment berhasil disimpan.');
    }

    private function applyAssessmentSiteScope($query, User $adminUser): void
    {
        $site = $adminUser->normalizedSite();

        $query->where(function ($q) use ($site): void {
            $q->where('site', $site)
                ->orWhere(function ($subQuery) use ($site): void {
                    $subQuery->whereNull('site')
                        ->whereHas('user', fn ($userQuery) => $userQuery->where('site', $site));
                });
        });
    }

    private function authorizeAssessmentSite(Assessment $assessment, User $adminUser): void
    {
        abort_unless(
            $adminUser->canViewAllSites()
                || $assessment->site === $adminUser->normalizedSite()
                || ($assessment->site === null && $assessment->user?->normalizedSite() === $adminUser->normalizedSite()),
            403
        );
    }
}
