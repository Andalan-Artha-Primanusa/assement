<?php

return [
    'max_security_blocks' => env('ASSESSMENT_MAX_SECURITY_BLOCKS', 2),
    'default_access_days' => env('ASSESSMENT_DEFAULT_ACCESS_DAYS', 7),
    'default_duration_minutes' => env('ASSESSMENT_DEFAULT_DURATION_MINUTES', 120),
    'max_attempts' => env('ASSESSMENT_MAX_ATTEMPTS', 1),
];
