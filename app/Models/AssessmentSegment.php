<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentSegment extends Model
{
    protected $fillable = [
        'assessment_id',
        'type',
        'duration_minutes',
        'order_index',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function remainingSeconds(): int
    {
        if (! $this->isInProgress() || ! $this->started_at) {
            return $this->duration_minutes * 60;
        }

        $elapsed = (int) $this->started_at->diffInSeconds(now());
        $total = $this->duration_minutes * 60;

        return max(0, $total - $elapsed);
    }

    public function questions()
    {
        return $this->assessment->answers()
            ->whereHas('question', fn ($q) => $q->where('type', $this->type))
            ->get();
    }
}
