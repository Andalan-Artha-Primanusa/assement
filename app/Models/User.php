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

    public function canManageType(string $type): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_MEKANIK && $type === 'mekanik') {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_OPERATION && $type === 'operator') {
            return true;
        }

        if ($this->role === self::ROLE_ADMIN_SHE && $type === 'she') {
            return true;
        }

        return false;
    }

    public function visiblePackageTypes(): array
    {
        if ($this->isSuperAdmin()) {
            return ['mekanik', 'operator', 'she'];
        }

        if ($this->role === self::ROLE_ADMIN_MEKANIK) {
            return ['mekanik'];
        }

        if ($this->role === self::ROLE_ADMIN_OPERATION) {
            return ['operator'];
        }

        if ($this->role === self::ROLE_ADMIN_SHE) {
            return ['she'];
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
