<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SheReviewController extends Controller
{
    public function index(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $assessments = Assessment::with('user', 'questionPackage')
            ->where('status', Assessment::STATUS_PENDING_REVIEW)
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->whereHas('questionPackage', function ($q) use ($visibleTypes): void {
                    $q->whereIn('type', $visibleTypes);
                });
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

        return view('admin.she-review.index', compact('assessments'));
    }

    public function show(Assessment $assessment): View
    {
        abort_unless(
            in_array($assessment->status, [Assessment::STATUS_PENDING_REVIEW, Assessment::STATUS_GRADED]),
            404
        );

        $assessment->load('user', 'questionPackage', 'answers.question');

        $answers = $assessment->answers->filter(function ($answer) {
            return $answer->question && ($answer->question->isEssay() || $answer->question->isUpload());
        });

        return view('admin.she-review.show', compact('assessment', 'answers'));
    }

    public function grade(Request $request, Assessment $assessment): RedirectResponse
    {
        abort_unless($assessment->status === Assessment::STATUS_PENDING_REVIEW, 400);

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

        $totalScore = 0;
        $count = 0;

        foreach ($essayUploadAnswers as $answer) {
            if (isset($validated['scores'][$answer->id])) {
                $answer->update([
                    'score' => $validated['scores'][$answer->id],
                    'review_notes' => $validated['notes'][$answer->id] ?? null,
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                ]);

                $totalScore += $validated['scores'][$answer->id];
                $count++;
            }
        }

        $mcAnswers = $assessment->answers->filter(function ($answer) {
            return $answer->question && $answer->question->isMultipleChoice();
        });

        $mcScore = 0;
        if ($mcAnswers->count() > 0) {
            $mcCorrect = $mcAnswers->where('is_correct', true)->count();
            $mcScore = ($mcCorrect / $mcAnswers->count()) * 100;
        }

        $essayScore = $count > 0 ? $totalScore / $count : 0;

        $mcWeight = 0.5;
        $essayWeight = 0.5;

        if ($mcAnswers->count() === 0) {
            $finalScore = $essayScore;
        } elseif ($count === 0) {
            $finalScore = $mcScore;
        } else {
            $finalScore = round(($mcScore * $mcWeight) + ($essayScore * $essayWeight), 2);
        }

        $assessment->update([
            'score' => $finalScore,
            'status' => Assessment::STATUS_GRADED,
        ]);

        ActivityLog::log('she_review', 'Review SHE assessment #'.$assessment->id, Assessment::class, $assessment->id);

        return redirect()->route('admin.she-review.index')
            ->with('status', 'Nilai SHE assessment berhasil disimpan.');
    }
}
