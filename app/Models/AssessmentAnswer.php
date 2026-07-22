<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'assessment_id',
        'question_id',
        'position',
        'selected_option',
        'is_correct',
        'answer_text',
        'file_path',
        'score',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'score' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    public function needsReview(): bool
    {
        return ! $this->isReviewed()
            && $this->question
            && ($this->question->isEssay() || $this->question->isUpload());
    }
}
