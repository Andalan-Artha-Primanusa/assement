<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_ESSAY = 'essay';
    public const TYPE_UPLOAD = 'upload';

    protected $fillable = [
        'question_package_id',
        'type',
        'category',
        'difficulty',
        'text',
        'image',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'points',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function questionPackage(): BelongsTo
    {
        return $this->belongsTo(QuestionPackage::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AssessmentAnswer::class);
    }

    public function isMultipleChoice(): bool
    {
        return $this->type === self::TYPE_MULTIPLE_CHOICE;
    }

    public function isEssay(): bool
    {
        return $this->type === self::TYPE_ESSAY;
    }

    public function isUpload(): bool
    {
        return $this->type === self::TYPE_UPLOAD;
    }

    public function optionText(?string $option): string
    {
        return match ($option) {
            'a' => $this->option_a ?? '',
            'b' => $this->option_b ?? '',
            'c' => $this->option_c ?? '',
            'd' => $this->option_d ?? '',
            default => '',
        };
    }

    public function pointValue(): float
    {
        return max(0.01, (float) ($this->points ?? 1));
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return route('files.show', [
            'path' => $this->image,
            'v' => $this->updated_at?->timestamp ?? time(),
        ]);
    }
}
