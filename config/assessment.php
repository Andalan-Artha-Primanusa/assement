<?php

return [
    'max_security_blocks' => env('ASSESSMENT_MAX_SECURITY_BLOCKS', 2),
    'question_limit' => env('ASSESSMENT_QUESTION_LIMIT', 12),
    'default_access_days' => env('ASSESSMENT_DEFAULT_ACCESS_DAYS', 7),
    'default_duration_minutes' => env('ASSESSMENT_DEFAULT_DURATION_MINUTES', 120),
    'max_attempts' => env('ASSESSMENT_MAX_ATTEMPTS', 1),
    'she_default_segments' => [
        ['type' => 'multiple_choice', 'duration' => env('ASSESSMENT_SHE_PG_MINUTES', 30)],
        ['type' => 'essay', 'duration' => env('ASSESSMENT_SHE_ESSAY_MINUTES', 45)],
        ['type' => 'upload', 'duration' => env('ASSESSMENT_SHE_UPLOAD_MINUTES', 30)],
    ],
];
