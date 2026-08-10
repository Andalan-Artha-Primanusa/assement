<?php

namespace App\Support;

use Illuminate\Support\Collection;

class SheScore
{
    /**
     * @param  Collection<int, \App\Models\AssessmentAnswer>  $answers
     * @return array{pg:?float,essay:?float,upload:?float,final:float}
     */
    public static function calculate(Collection $answers): array
    {
        $mcAnswers = $answers->filter(fn ($answer) => $answer->question && $answer->question->isAutoScored());
        $essayAnswers = $answers->filter(fn ($answer) => $answer->question && $answer->question->isEssay());
        $uploadAnswers = $answers->filter(fn ($answer) => $answer->question && $answer->question->isUpload());

        $pgScore = $mcAnswers->isNotEmpty()
            ? round(($mcAnswers->where('is_correct', true)->count() / $mcAnswers->count()) * 100, 2)
            : null;
        $essayScore = self::manualAverage($essayAnswers);
        $uploadScore = self::manualAverage($uploadAnswers);

        $segmentScores = collect([$pgScore, $essayScore, $uploadScore])->filter(fn ($score) => $score !== null);

        return [
            'pg' => $pgScore,
            'essay' => $essayScore,
            'upload' => $uploadScore,
            'final' => $segmentScores->isNotEmpty() ? round($segmentScores->avg(), 2) : 0.0,
        ];
    }

    /**
     * @param  Collection<int, \App\Models\AssessmentAnswer>  $answers
     */
    private static function manualAverage(Collection $answers): ?float
    {
        if ($answers->isEmpty() || ! $answers->every(fn ($answer) => $answer->score !== null)) {
            return null;
        }

        return round($answers->avg('score'), 2);
    }
}
