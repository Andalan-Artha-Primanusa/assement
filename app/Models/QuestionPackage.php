<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionPackage extends Model
{
    protected $fillable = [
        'name',
        'type',
        'level',
        'description',
        'is_active',
        'is_certificate',
        'has_segments',
        'created_by',
        'min_score_pertimbangan',
        'min_score_lolos',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_certificate' => 'boolean',
            'has_segments' => 'boolean',
            'min_score_pertimbangan' => 'decimal:2',
            'min_score_lolos' => 'decimal:2',
        ];
    }

    public function getGrade(float $score): string
    {
        if ($this->min_score_lolos !== null && $score >= (float) $this->min_score_lolos) {
            return 'Lolos';
        }

        if ($this->min_score_pertimbangan !== null && $score >= (float) $this->min_score_pertimbangan) {
            return 'Dipertimbangkan';
        }

        return 'Tidak Lolos';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'question_package_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeQuestions(): HasMany
    {
        return $this->hasMany(Question::class, 'question_package_id')->where('is_active', true);
    }
}
