<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'assessment_access_expires_at',
        'assessment_duration_minutes',
        'max_attempts',
        'question_package_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'assessment_access_expires_at' => 'datetime',
            'assessment_duration_minutes' => 'integer',
            'max_attempts' => 'integer',
            'question_package_id' => 'integer',
        ];
    }

    public function questionPackage(): BelongsTo
    {
        return $this->belongsTo(QuestionPackage::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function createdPackages(): HasMany
    {
        return $this->hasMany(QuestionPackage::class, 'created_by');
    }

    public function canAccessAssessment(): bool
    {
        return $this->is_admin
            || $this->assessment_access_expires_at === null
            || $this->assessment_access_expires_at->isFuture();
    }

    public function assessmentDurationMinutes(): int
    {
        return max(1, $this->assessment_duration_minutes ?? (int) config('assessment.default_duration_minutes', 120));
    }
}
