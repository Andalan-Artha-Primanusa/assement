<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_GRADED = 'graded';

    protected $fillable = [
        'user_id',
        'question_package_id',
        'status',
        'total_questions',
        'correct_answers',
        'score',
        'started_at',
        'duration_minutes',
        'ends_at',
        'submitted_at',
        'blocked_at',
        'block_reason',
        'unlocked_at',
        'security_violations',
        'auto_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'question_package_id' => 'integer',
            'started_at' => 'datetime',
            'duration_minutes' => 'integer',
            'ends_at' => 'datetime',
            'submitted_at' => 'datetime',
            'blocked_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'security_violations' => 'integer',
            'auto_submitted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questionPackage(): BelongsTo
    {
        return $this->belongsTo(QuestionPackage::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class)->orderBy('position');
    }

    public function segments(): HasMany
    {
        return $this->hasMany(AssessmentSegment::class)->orderBy('order_index');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isPendingReview(): bool
    {
        return $this->isSubmitted()
            && $this->status === self::STATUS_PENDING_REVIEW
            && $this->questionPackage?->type === QuestionPackage::TYPE_SHE;
    }

    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    public function isBlocked(): bool
    {
        return ! $this->isSubmitted() && $this->blocked_at !== null && (
            $this->unlocked_at === null || $this->unlocked_at->lessThan($this->blocked_at)
        );
    }

    public function isExpired(): bool
    {
        return ! $this->isSubmitted()
            && $this->ends_at !== null
            && $this->ends_at->isPast();
    }

    public function hasEssayOrUploadQuestions(): bool
    {
        return $this->answers()->whereHas('question', function ($query): void {
            $query->whereIn('type', ['essay', 'upload']);
        })->exists();
    }

    public function needsReviewCount(): int
    {
        return $this->answers()
            ->whereNull('reviewed_at')
            ->whereHas('question', function ($query): void {
                $query->whereIn('type', ['essay', 'upload']);
            })
            ->count();
    }
}
