<?php

namespace App\Support;

use App\Models\Question;
use App\Models\QuestionPackage;

class AssessmentSegmentConfig
{
    public const SHE_SEGMENT_TYPES = [
        Question::TYPE_MULTIPLE_CHOICE,
        Question::TYPE_ESSAY,
        Question::TYPE_UPLOAD,
    ];

    public static function forPackage(?QuestionPackage $package, ?array $config = null): array
    {
        if (! $package || (! $package->has_segments && $package->type !== QuestionPackage::TYPE_SHE)) {
            return [];
        }

        $normalized = self::normalize($config);

        if ($normalized === []) {
            $normalized = self::defaultSegments();
        }

        if ($package->type === QuestionPackage::TYPE_SHE) {
            return self::forShe($normalized);
        }

        return self::uniqueByType($normalized);
    }

    public static function defaultSegments(): array
    {
        return self::normalize(config('assessment.she_default_segments', []));
    }

    private static function forShe(array $segments): array
    {
        $defaults = self::defaultSegments();
        $fallbackDurations = [
            Question::TYPE_MULTIPLE_CHOICE => 30,
            Question::TYPE_ESSAY => 45,
            Question::TYPE_UPLOAD => 30,
        ];

        $defaultDurationsByType = [];
        foreach ($defaults as $segment) {
            $defaultDurationsByType[$segment['type']] = $segment['duration'];
        }

        $durationsByType = [];
        $positionalDurations = [];

        foreach ($segments as $segment) {
            $duration = max(1, (int) ($segment['duration'] ?? 0));
            $type = (string) ($segment['type'] ?? '');

            $positionalDurations[] = $duration;

            if (! isset($durationsByType[$type])) {
                $durationsByType[$type] = $duration;
            }
        }

        return collect(self::SHE_SEGMENT_TYPES)
            ->map(fn (string $type, int $index): array => [
                'type' => $type,
                'duration' => $durationsByType[$type]
                    ?? $positionalDurations[$index]
                    ?? $defaultDurationsByType[$type]
                    ?? $fallbackDurations[$type],
            ])
            ->values()
            ->all();
    }

    private static function uniqueByType(array $segments): array
    {
        $seen = [];

        return collect($segments)
            ->filter(function (array $segment) use (&$seen): bool {
                if (isset($seen[$segment['type']])) {
                    return false;
                }

                $seen[$segment['type']] = true;

                return true;
            })
            ->values()
            ->all();
    }

    private static function normalize(?array $config): array
    {
        if (! is_array($config)) {
            return [];
        }

        return collect($config)
            ->map(function ($segment): ?array {
                if (! is_array($segment)) {
                    return null;
                }

                $type = (string) ($segment['type'] ?? '');
                $duration = (int) ($segment['duration'] ?? 0);

                if (! in_array($type, self::SHE_SEGMENT_TYPES, true) || $duration <= 0) {
                    return null;
                }

                return [
                    'type' => $type,
                    'duration' => $duration,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
