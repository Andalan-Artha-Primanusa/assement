<?php

namespace App\Services;

use App\Models\Assessment;

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
        $assessment->load('answers.question');

        $hasEssayOrUpload = $assessment->answers->contains(function ($answer) {
            return $answer->question && ($answer->question->isEssay() || $answer->question->isUpload());
        });

        $correctAnswers = 0;
        $mcCount = 0;

        foreach ($assessment->answers as $answer) {
            if ($answer->question && $answer->question->isMultipleChoice()) {
                $selected = $submittedAnswers[$answer->id] ?? $answer->selected_option;
                $isCorrect = $selected !== null && $selected === $answer->question->correct_option;

                $answer->update([
                    'selected_option' => $selected,
                    'is_correct' => $isCorrect,
                ]);

                if ($isCorrect) {
                    $correctAnswers++;
                }
                $mcCount++;
            } else {
                $answer->update([
                    'answer_text' => $submittedAnswers[$answer->id] ?? $answer->answer_text,
                ]);
            }
        }

        $totalQuestions = $assessment->answers->count();

        $status = $hasEssayOrUpload && ! $autoSubmitted
            ? Assessment::STATUS_PENDING_REVIEW
            : Assessment::STATUS_IN_PROGRESS;

        $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;

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
                } else {
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
