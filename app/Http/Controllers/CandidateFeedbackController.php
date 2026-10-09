<?php

namespace App\Http\Controllers;

use App\Models\InterviewAssessment;
use App\Models\InterviewCandidateFeedback;
use Illuminate\Http\Request;

class CandidateFeedbackController extends Controller
{
    /**
     * Show the public feedback form (no login required).
     */
    public function show(string $token)
    {
        $feedback = InterviewCandidateFeedback::where('token', $token)
            ->with(['assessment.template.categories.aspects'])
            ->firstOrFail();

        if ($feedback->isSubmitted()) {
            return view('feedback.submitted', compact('feedback'));
        }

        return view('feedback.show', compact('feedback'));
    }

    /**
     * Store the candidate's self-assessment feedback.
     */
    public function store(Request $request, string $token)
    {
        $feedback = InterviewCandidateFeedback::where('token', $token)
            ->with(['assessment.template.categories.aspects'])
            ->firstOrFail();

        if ($feedback->isSubmitted()) {
            return redirect()->route('feedback.show', $token)
                ->with('error', 'Feedback sudah pernah disubmit sebelumnya.');
        }

        $request->validate([
            'evaluator_name'  => ['required', 'string', 'max:255'],
            'interview_date' => ['required', 'date'],
            'feedback'       => ['nullable', 'string', 'max:3000'],
            'scores'         => ['nullable', 'array'],
            'scores.*.score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'scores.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $template = $feedback->assessment->template;

        // Collect all aspects for calculation
        $allAspects = $template->categories->flatMap(fn ($cat) => $cat->aspects);
        $totalWeight = $allAspects->sum('weight');
        $submittedScores = $request->input('scores', []);

        // Weighted calculation
        $weightedSum = 0;
        $countAspects = $allAspects->count();
        $scoresData = [];

        foreach ($allAspects as $aspect) {
            $scoreData = $submittedScores[$aspect->id] ?? [];
            $score = isset($scoreData['score']) ? (int) $scoreData['score'] : null;
            $notes = $scoreData['notes'] ?? null;

            $scoresData[$aspect->id] = [
                'score' => $score,
                'notes' => $notes,
            ];

            if ($score !== null && $totalWeight > 0) {
                $weightedSum += $score * $aspect->weight;
            }
        }

        $maxPossibleWeightedScore = $totalWeight > 0 ? $totalWeight * 5 : $countAspects * 5;
        $totalScore = collect($scoresData)->sum(fn ($s) => (int) ($s['score'] ?? 0));
        $averageScore = $countAspects > 0 ? $totalScore / $countAspects : 0;
        $percentage = $maxPossibleWeightedScore > 0
            ? ($weightedSum / $maxPossibleWeightedScore) * 100
            : 0;

        $submissions = $feedback->submissions ?? [];
        $submissions[] = [
            'evaluator_name' => $request->evaluator_name,
            'feedback' => $request->feedback,
            'scores' => $scoresData,
            'total_score' => $totalScore,
            'average_score' => round($averageScore, 2),
            'percentage' => round($percentage, 2),
            'submitted_at' => now()->toISOString(),
        ];

        $feedback->update([
            'submissions' => $submissions,
            'submitted_at' => count($submissions) >= 5 ? now() : null,
        ]);

        $feedback->assessment->update([
            'interview_date' => $request->interview_date,
        ]);

        $bestFeedback = collect($submissions)->sortByDesc('percentage')->first();

        if ($bestFeedback) {
            $assessment = $feedback->assessment->load('template');
            $recommendation = 'TIDAK DIREKOMENDASIKAN';
            if ((float) $bestFeedback['percentage'] >= (float) $assessment->template->min_recommended_percentage) {
                $recommendation = 'DIREKOMENDASIKAN';
            } elseif ((float) $bestFeedback['percentage'] >= (float) $assessment->template->min_considered_percentage) {
                $recommendation = 'DIPERTIMBANGKAN';
            }

            $assessment->update([
                'total_score' => $bestFeedback['total_score'],
                'average_score' => $bestFeedback['average_score'],
                'percentage' => $bestFeedback['percentage'],
                'recommendation' => $recommendation,
            ]);
        }

        return redirect()->route('feedback.show', $token)
            ->with('success', 'Terima kasih! Feedback Anda berhasil dikirimkan.');
    }
}
