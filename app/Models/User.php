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

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN_MEKANIK = 'admin_mekanik';
    public const ROLE_ADMIN_OPERATION = 'admin_operation';
    public const ROLE_ADMIN_SHE = 'admin_she';
    public const ROLE_ADMIN_HR = 'admin_hr';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'assessment_access_expires_at',
        'assessment_duration_minutes',
        'max_attempts',
        'question_package_id',
        'operator_assessment_category_id',
        'segment_config',
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
            'assessment_access_expires_at' => 'datetime',
            'assessment_duration_minutes' => 'integer',
            'max_attempts' => 'integer',
            'question_package_id' => 'integer',
            'operator_assessment_category_id' => 'integer',
            'segment_config' => 'array',
        ];
    }

    public function questionPackage(): BelongsTo
    {
        return $this->belongsTo(QuestionPackage::class);
    }

    public function operatorAssessmentCategory(): BelongsTo
    {
        return $this->belongsTo(OperatorAssessmentCategory::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function createdPackages(): HasMany
    {
        return $this->hasMany(QuestionPackage::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN_MEKANIK,
            self::ROLE_ADMIN_OPERATION,
            self::ROLE_ADMIN_SHE,
            self::ROLE_ADMIN_HR,
        ]);
    }

    public function isAdminMekanik(): bool
    {
        return $this->role === self::ROLE_ADMIN_MEKANIK;
    }

    public function isAdminOperation(): bool
    {
        return $this->role === self::ROLE_ADMIN_OPERATION;
    }

    public function isAdminShe(): bool
    {
        return $this->role === self::ROLE_ADMIN_SHE;
    }

    public function isAdminHr(): bool
    {
        return $this->role === self::ROLE_ADMIN_HR;
    }

    public function canManageType(string $type): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_MEKANIK && $type === QuestionPackage::TYPE_MEKANIK) {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_OPERATION && $type === QuestionPackage::TYPE_OPERATOR) {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_SHE && $type === QuestionPackage::TYPE_SHE) {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_HR && $type === QuestionPackage::TYPE_HR) {
            return true;
        }

        return false;
    }

    public function visiblePackageTypes(): array
    {
        if ($this->isSuperAdmin()) {
            return QuestionPackage::TYPES;
        }

        if ($this->role === self::ROLE_ADMIN_MEKANIK) {
            return [QuestionPackage::TYPE_MEKANIK];
        }

        if ($this->role === self::ROLE_ADMIN_OPERATION) {
            return [QuestionPackage::TYPE_OPERATOR];
        }

        if ($this->role === self::ROLE_ADMIN_SHE) {
            return [QuestionPackage::TYPE_SHE];
        }

        if ($this->role === self::ROLE_ADMIN_HR) {
            return [QuestionPackage::TYPE_HR];
        }

        return [];
    }

    public function canAccessAssessment(): bool
    {
        return $this->isAdmin()
            || $this->assessment_access_expires_at === null
            || $this->assessment_access_expires_at->isFuture();
    }

    public function assessmentDurationMinutes(): int
    {
        return max(1, $this->assessment_duration_minutes ?? (int) config('assessment.default_duration_minutes', 120));
    }
}
