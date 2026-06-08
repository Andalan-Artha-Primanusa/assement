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
        if ($request->user()->is_admin) {
            return $this->adminDashboard();
        }

        return $this->userDashboard($request);
    }

    private function adminDashboard(): View
    {
        $submitted = Assessment::query()->whereNotNull('submitted_at');
        $averageScore = (float) (clone $submitted)->avg('score');

        $stats = [
            'questions' => Question::count(),
            'active_questions' => Question::where('is_active', true)->count(),
            'packages' => QuestionPackage::count(),
            'users' => User::where('is_admin', false)->count(),
            'assessments' => (clone $submitted)->count(),
            'blocked_assessments' => Assessment::whereNotNull('blocked_at')
                ->whereNull('submitted_at')
                ->where(function ($query): void {
                    $query->whereNull('unlocked_at')
                        ->orWhereColumn('unlocked_at', '<', 'blocked_at');
                })
                ->count(),
            'average_score' => round($averageScore, 1),
        ];

        $scoreBuckets = [
            '0-59' => Assessment::whereNotNull('submitted_at')->whereBetween('score', [0, 59.99])->count(),
            '60-69' => Assessment::whereNotNull('submitted_at')->whereBetween('score', [60, 69.99])->count(),
            '70-79' => Assessment::whereNotNull('submitted_at')->whereBetween('score', [70, 79.99])->count(),
            '80-89' => Assessment::whereNotNull('submitted_at')->whereBetween('score', [80, 89.99])->count(),
            '90-100' => Assessment::whereNotNull('submitted_at')->whereBetween('score', [90, 100])->count(),
        ];

        $pending = Assessment::whereNull('submitted_at')
            ->whereNull('blocked_at')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->count();
        $blocked = Assessment::whereNotNull('blocked_at')
            ->whereNull('submitted_at')
            ->where(function ($q) {
                $q->whereNull('unlocked_at')->orWhereColumn('unlocked_at', '<', 'blocked_at');
            })
            ->count();

        $dailyAttempts = Assessment::query()
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

        $packageData = QuestionPackage::all()->map(function ($p) {
            $avg = Assessment::where('question_package_id', $p->id)
                ->whereNotNull('submitted_at')
                ->avg('score');
            return [
                'name' => $p->name,
                'avg' => round((float) $avg, 1),
            ];
        });

        $categoryData = Question::query()
            ->select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $answerCategoryStats = DB::table('assessment_answers')
            ->join('questions', 'assessment_answers.question_id', '=', 'questions.id')
            ->join('assessments', 'assessment_answers.assessment_id', '=', 'assessments.id')
            ->whereNotNull('assessments.submitted_at')
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

        $latestAssessments = Assessment::with('user')
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        $blockedAssessments = Assessment::with('user')
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
        ));
    }

    private function userDashboard(Request $request): View
    {
        $assignedPackage = $request->user()->questionPackage;
        $activeQuestionQuery = Question::query()->where('is_active', true);

        if ($assignedPackage && $assignedPackage->is_active) {
            $activeQuestionQuery->where('question_package_id', $assignedPackage->id);
        } elseif ($assignedPackage && ! $assignedPackage->is_active) {
            $activeQuestionQuery->whereRaw('1 = 0');
        }

        $activeQuestionCount = (clone $activeQuestionQuery)->count();
        $openAssessment = $request->user()
            ->assessments()
            ->whereNull('submitted_at')
            ->latest()
            ->first();
        $assessments = $request->user()
            ->assessments()
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->paginate(10);

        return view('dashboard', compact('activeQuestionCount', 'openAssessment', 'assessments', 'assignedPackage'));
    }
}
