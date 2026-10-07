<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InterviewCandidateFeedback extends Model
{
    protected $table = 'interview_candidate_feedbacks';

    protected $fillable = [
        'interview_assessment_id',
        'token',
        'evaluator_name',
        'feedback',
        'scores',
        'total_score',
        'average_score',
        'percentage',
        'submitted_at',
        'submissions',
    ];

    protected $casts = [
        'scores' => 'array',
        'total_score' => 'decimal:2',
        'average_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'submitted_at' => 'datetime',
        'submissions' => 'array',
    ];

    public function assessment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InterviewAssessment::class, 'interview_assessment_id');
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function isSubmitted(): bool
    {
        return count($this->submissions ?? []) >= 5;
    }

    public function getFeedbackLinkAttribute(): string
    {
        return route('feedback.show', $this->token);
    }
}
