<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\QuestionPackage;

class AssessmentSecurity
{
    /**
     * @param  array<int|string, string|null>  $submittedAnswers
     * @return array{blocked: bool, submitted: bool, violations: int}
     */
    public function recordViolation(Assessment $assessment, string $reason, array $submittedAnswers = []): array
    {
        if ($assessment->isSubmitted()) {
            return $this->status($assessment);
        }

        if ($assessment->isBlocked()) {
            return $this->status($assessment);
        }

        $assessment->increment('security_violations');
        $assessment->refresh();

        if ($assessment->security_violations > (int) config('assessment.max_security_blocks', 2)) {
            $this->finishAssessment($assessment, $submittedAnswers, true);

            return $this->status($assessment->fresh());
        }

        $this->syncSelectedAnswers($assessment, $submittedAnswers);

        $assessment->update([
            'blocked_at' => now(),
            'block_reason' => $reason,
            'unlocked_at' => null,
        ]);

        return $this->status($assessment->fresh());
    }

    /**
     * @param  array<int|string, string|null>  $submittedAnswers
     */
    public function finishAssessment(Assessment $assessment, array $submittedAnswers = [], bool $autoSubmitted = false): void
    {
        $assessment->load('answers.question', 'questionPackage');

        $hasEssayOrUpload = $assessment->answers->contains(function ($answer) {
            return $answer->question && ($answer->question->isEssay() || $answer->question->isUpload());
        });

        $needsManualReview = $assessment->questionPackage?->type === QuestionPackage::TYPE_SHE
            && $hasEssayOrUpload;
        $usesWeightedScore = $assessment->questionPackage?->type === QuestionPackage::TYPE_HR;

        $correctAnswers = 0;
        $multipleChoiceCount = 0;
        $weightedCorrectPoints = 0.0;
        $weightedTotalPoints = 0.0;

        foreach ($assessment->answers as $answer) {
            if ($answer->question && $answer->question->isMultipleChoice()) {
                $multipleChoiceCount++;
                $points = $answer->question->pointValue();
                $weightedTotalPoints += $points;
                $selected = $submittedAnswers[$answer->id] ?? $answer->selected_option;
                $isCorrect = $selected !== null && $selected === $answer->question->correct_option;

                $answer->update([
                    'selected_option' => $selected,
                    'is_correct' => $isCorrect,
                ]);

                if ($isCorrect) {
                    $correctAnswers++;
                    $weightedCorrectPoints += $points;
                }
            } elseif ($answer->question && $answer->question->isEssay()) {
                $answer->update([
                    'answer_text' => $submittedAnswers[$answer->id] ?? $answer->answer_text,
                ]);
            }
        }

        $totalQuestions = $assessment->answers->count();

        $status = $needsManualReview
            ? Assessment::STATUS_PENDING_REVIEW
            : Assessment::STATUS_GRADED;

        $autoScoredQuestions = $multipleChoiceCount > 0 ? $multipleChoiceCount : $totalQuestions;
        $score = $usesWeightedScore && $weightedTotalPoints > 0
            ? round(($weightedCorrectPoints / $weightedTotalPoints) * 100, 2)
            : ($autoScoredQuestions > 0 ? round(($correctAnswers / $autoScoredQuestions) * 100, 2) : 0);

        $assessment->update([
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'score' => $score,
            'status' => $status,
            'submitted_at' => now(),
            'auto_submitted_at' => $autoSubmitted ? now() : null,
        ]);
    }

    /**
     * @param  array<int|string, string|null>  $submittedAnswers
     */
    public function syncSelectedAnswers(Assessment $assessment, array $submittedAnswers): void
    {
        if ($submittedAnswers === []) {
            return;
        }

        $assessment->loadMissing('answers');

        foreach ($assessment->answers as $answer) {
            if (array_key_exists($answer->id, $submittedAnswers)) {
                if ($answer->question && $answer->question->isMultipleChoice()) {
                    $answer->update([
                        'selected_option' => $submittedAnswers[$answer->id],
                    ]);
                } elseif ($answer->question && $answer->question->isEssay()) {
                    $answer->update([
                        'answer_text' => $submittedAnswers[$answer->id],
                    ]);
                }
            }
        }
    }

    /**
     * @return array{blocked: bool, submitted: bool, violations: int}
     */
    private function status(Assessment $assessment): array
    {
        return [
            'blocked' => $assessment->isBlocked(),
            'submitted' => $assessment->isSubmitted(),
            'violations' => $assessment->security_violations,
        ];
    }
}
