<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Question;
use App\Models\QuestionPackage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()->isAdmin()) {
            return $this->adminDashboard($request);
        }

        return $this->userDashboard($request);
    }

    private function adminDashboard(Request $request): View
    {
        $adminUser = $request->user();
        $visibleTypes = $adminUser->visiblePackageTypes();

        $selectedType = $request->string('type')->toString();
        if ($adminUser->isSuperAdmin() && $selectedType && in_array($selectedType, QuestionPackage::TYPES, true)) {
            $visibleTypes = [$selectedType];
        }

        $baseQuery = Assessment::query()
            ->when(! $adminUser->isSuperAdmin(), function ($query) use ($visibleTypes): void {
                $query->where(function ($query) use ($visibleTypes): void {
                    $query->whereHas('questionPackage', function ($q) use ($visibleTypes): void {
                        $q->whereIn('type', $visibleTypes);
                    })->orWhereHas('user.questionPackage', function ($q) use ($visibleTypes): void {
                        $q->whereIn('type', $visibleTypes);
                    });
                });
            })
            ->when($adminUser->isSuperAdmin() && $selectedType, function ($query) use ($selectedType): void {
                $query->where(function ($query) use ($selectedType): void {
                    $query->whereHas('questionPackage', function ($q) use ($selectedType): void {
                        $q->where('type', $selectedType);
                    })->orWhereHas('user.questionPackage', function ($q) use ($selectedType): void {
                        $q->where('type', $selectedType);
                    });
                });
            })
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $this->applyAssessmentSiteScope($query, $adminUser);
            });

        $submitted = (clone $baseQuery)->whereNotNull('submitted_at');
        $gradedSubmitted = (clone $submitted)->where('status', Assessment::STATUS_GRADED);
        $averageScore = (float) (clone $gradedSubmitted)->avg('score');

        $packageIds = QuestionPackage::whereIn('type', $visibleTypes)->pluck('id');
        $participantQuery = User::where('role', User::ROLE_USER)
            ->whereIn('question_package_id', $packageIds)
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $query->where('site', $adminUser->normalizedSite());
            });
        $notStarted = (clone $participantQuery)
            ->whereDoesntHave('assessments', function ($query): void {
                $query->whereColumn('assessments.question_package_id', 'users.question_package_id')
                    ->where(function ($categoryQuery): void {
                        $categoryQuery->whereColumn('assessments.operator_assessment_category_id', 'users.operator_assessment_category_id')
                            ->orWhere(function ($nullCategoryQuery): void {
                                $nullCategoryQuery->whereNull('assessments.operator_assessment_category_id')
                                    ->whereNull('users.operator_assessment_category_id');
                            });
                    });
            })
            ->count();

        $stats = [
            'questions' => Question::whereIn('question_package_id', $packageIds)->count(),
            'active_questions' => Question::whereIn('question_package_id', $packageIds)->where('is_active', true)->count(),
            'packages' => QuestionPackage::whereIn('type', $visibleTypes)->count(),
            'users' => (clone $participantQuery)->count(),
            'assessments' => (clone $submitted)->count(),
            'not_started' => $notStarted,
            'blocked_assessments' => (clone $baseQuery)->whereNotNull('blocked_at')
                ->whereNull('submitted_at')
                ->where(function ($query): void {
                    $query->whereNull('unlocked_at')
                        ->orWhereColumn('unlocked_at', '<', 'blocked_at');
                })
                ->count(),
            'pending_review' => (clone $baseQuery)
                ->where('status', Assessment::STATUS_PENDING_REVIEW)
                ->whereHas('questionPackage', function ($q): void {
                    $q->where('type', QuestionPackage::TYPE_SHE);
                })
                ->count(),
            'average_score' => round($averageScore, 1),
        ];

        $scoreBuckets = [
            '0-59' => (clone $baseQuery)->where('status', Assessment::STATUS_GRADED)->whereBetween('score', [0, 59.99])->count(),
            '60-69' => (clone $baseQuery)->where('status', Assessment::STATUS_GRADED)->whereBetween('score', [60, 69.99])->count(),
            '70-79' => (clone $baseQuery)->where('status', Assessment::STATUS_GRADED)->whereBetween('score', [70, 79.99])->count(),
            '80-89' => (clone $baseQuery)->where('status', Assessment::STATUS_GRADED)->whereBetween('score', [80, 89.99])->count(),
            '90-100' => (clone $baseQuery)->where('status', Assessment::STATUS_GRADED)->whereBetween('score', [90, 100])->count(),
        ];

        $pending = (clone $baseQuery)->whereNull('submitted_at')
            ->whereNull('blocked_at')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->count();
        $blocked = (clone $baseQuery)->whereNotNull('blocked_at')
            ->whereNull('submitted_at')
            ->where(function ($q) {
                $q->whereNull('unlocked_at')->orWhereColumn('unlocked_at', '<', 'blocked_at');
            })
            ->count();

        $dailyAttempts = (clone $baseQuery)
            ->selectRaw('DATE(submitted_at) as label, COUNT(*) as total')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->keyBy('label');

        $dailyLabels = [];
        $dailyTotals = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = now()->subDays($i)->format('d/m');
            $dailyTotals[] = (int) ($dailyAttempts[$date]->total ?? 0);
        }

        $packageData = QuestionPackage::whereIn('type', $visibleTypes)->get()->map(function ($p) use ($baseQuery) {
            $avg = (clone $baseQuery)->where('question_package_id', $p->id)
                ->where('status', Assessment::STATUS_GRADED)
                ->avg('score');
            return [
                'name' => $p->name,
                'avg' => round((float) $avg, 1),
            ];
        });

        $categoryData = Question::query()
            ->whereIn('question_package_id', $packageIds)
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $answerCategoryStats = DB::table('assessment_answers')
            ->join('questions', 'assessment_answers.question_id', '=', 'questions.id')
            ->join('assessments', 'assessment_answers.assessment_id', '=', 'assessments.id')
            ->join('users', 'assessments.user_id', '=', 'users.id')
            ->whereNotNull('assessments.submitted_at')
            ->whereIn('questions.question_package_id', $packageIds)
            ->when($adminUser->hasSiteRestriction(), function ($query) use ($adminUser): void {
                $query->where(function ($q) use ($adminUser): void {
                    $q->where('assessments.site', $adminUser->normalizedSite())
                        ->orWhere(function ($subQuery) use ($adminUser): void {
                            $subQuery->whereNull('assessments.site')
                                ->where('users.site', $adminUser->normalizedSite());
                        });
                });
            })
            ->select(
                'questions.category',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN assessment_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct'),
            )
            ->groupBy('questions.category')
            ->get();

        $radarLabels = $answerCategoryStats->pluck('category')->toArray();
        $radarValues = $answerCategoryStats->map(function ($item) {
            return $item->total > 0 ? round(($item->correct / $item->total) * 100, 1) : 0;
        })->toArray();

        $latestAssessments = (clone $baseQuery)->with('user', 'questionPackage')
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        $blockedAssessments = (clone $baseQuery)->with('user')
            ->whereNotNull('blocked_at')
            ->whereNull('submitted_at')
            ->where(function ($query): void {
                $query->whereNull('unlocked_at')
                    ->orWhereColumn('unlocked_at', '<', 'blocked_at');
            })
            ->latest('blocked_at')
            ->limit(8)
            ->get();

        $chartData = [
            'submitted' => $stats['assessments'],
            'blocked' => $blocked,
            'pending' => $pending,
            'notStarted' => $notStarted,
            'dailyLabels' => $dailyLabels,
            'dailyTotals' => $dailyTotals,
            'packageLabels' => $packageData->pluck('name')->toArray(),
            'packageScores' => $packageData->pluck('avg')->toArray(),
            'categoryLabels' => $categoryData->pluck('category')->toArray(),
            'categoryTotals' => $categoryData->pluck('total')->toArray(),
            'radarLabels' => $radarLabels,
            'radarValues' => $radarValues,
        ];

        return view('admin.dashboard', compact(
            'stats',
            'scoreBuckets',
            'latestAssessments',
            'blockedAssessments',
            'chartData',
            'selectedType',
        ));
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

    private function userDashboard(Request $request): View
    {
        $user = $request->user();
        $assignedPackage = $user->questionPackage;
        $inviteCategoryId = $user->operator_assessment_category_id;
        $activeQuestionQuery = Question::query()->where('is_active', true);

        if ($assignedPackage && $assignedPackage->is_active) {
            $activeQuestionQuery->where('question_package_id', $assignedPackage->id);
        } elseif ($assignedPackage && ! $assignedPackage->is_active) {
            $activeQuestionQuery->whereRaw('1 = 0');
        }

        $activeQuestionCount = (clone $activeQuestionQuery)->count();
        $openAssessment = $user->assessments()
            ->with('questionPackage')
            ->whereNull('submitted_at')
            ->when($assignedPackage, fn ($query) => $query->where('question_package_id', $assignedPackage->id))
            ->when(
                $inviteCategoryId,
                fn ($query) => $query->where('operator_assessment_category_id', $inviteCategoryId),
                fn ($query) => $query->whereNull('operator_assessment_category_id')
            )
            ->latest()
            ->first();
        $maxAttempts = $user->max_attempts ?? (int) config('assessment.max_attempts', 1);
        $completedCount = $user->assessments()
            ->whereNotNull('submitted_at')
            ->when($assignedPackage, fn ($query) => $query->where('question_package_id', $assignedPackage->id))
            ->when(
                $inviteCategoryId,
                fn ($query) => $query->where('operator_assessment_category_id', $inviteCategoryId),
                fn ($query) => $query->whereNull('operator_assessment_category_id')
            )
            ->count();
        $remainingAttempts = max(0, $maxAttempts - $completedCount);
        $attemptLimitReached = $remainingAttempts <= 0;
        $currentAssessmentsQuery = $user->assessments()
            ->whereNotNull('submitted_at')
            ->when($assignedPackage, fn ($query) => $query->where('question_package_id', $assignedPackage->id))
            ->when(
                $inviteCategoryId,
                fn ($query) => $query->where('operator_assessment_category_id', $inviteCategoryId),
                fn ($query) => $query->whereNull('operator_assessment_category_id')
            );
        $latestAssessment = (clone $currentAssessmentsQuery)
            ->with('questionPackage', 'operatorAssessmentCategory')
            ->latest('submitted_at')
            ->first();
        $assessments = $user->assessments()
            ->with('questionPackage', 'operatorAssessmentCategory')
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->paginate(10);

        $accessExpired = ! $user->canAccessAssessment();

        return view('dashboard', compact(
            'activeQuestionCount',
            'openAssessment',
            'latestAssessment',
            'assessments',
            'assignedPackage',
            'accessExpired',
            'maxAttempts',
            'completedCount',
            'remainingAttempts',
            'attemptLimitReached',
        ));
    }
}
