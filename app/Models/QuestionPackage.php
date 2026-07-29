<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionPackage extends Model
{
    public const TYPE_MEKANIK = 'mekanik';
    public const TYPE_OPERATOR = 'operator';
    public const TYPE_SHE = 'she';
    public const TYPE_HR = 'hr';

    public const TYPES = [
        self::TYPE_MEKANIK,
        self::TYPE_OPERATOR,
        self::TYPE_SHE,
        self::TYPE_HR,
    ];

    public const LEVELS_BY_TYPE = [
        self::TYPE_MEKANIK => [
            'M1' => 'M1 - Senior Mechanic / Inspector',
            'M2' => 'M2 - Middle Mechanic',
            'M3' => 'M3 - Junior Mechanic',
            'T1' => 'T1 - Senior Tyreman',
            'T2' => 'T2 - Middle Tyreman',
            'T3' => 'T3 - Junior Tyreman',
            'AE1' => 'AE1 - Senior Auto Electrician',
            'AE2' => 'AE2 - Middle Auto Electrician',
            'AE3' => 'AE3 - Junior Auto Electrician',
            'W1' => 'W1 - Senior Welder',
            'W2' => 'W2 - Middle Welder',
            'W3' => 'W3 - Junior Welder',
        ],
        self::TYPE_OPERATOR => [],
        self::TYPE_SHE => [
            'Departement Head' => 'Departement Head',
            'Section Head' => 'Section Head',
            'Lead Of' => 'Lead Of',
        ],
        self::TYPE_HR => [
            'Dispatch Plant' => 'Dispatch Plant',
            'Dispatcher MCC' => 'Dispatcher MCC',
            'Admin Finance' => 'Admin Finance',
            'Admin Accounting' => 'Admin Accounting',
            'Admin SHE' => 'Admin SHE',
            'Admin HRGA' => 'Admin HRGA',
            'Admin Operation' => 'Admin Operation',
            'Admin Engineering' => 'Admin Engineering',
        ],
    ];

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

    public static function typeLabel(?string $type): string
    {
        return match ($type) {
            self::TYPE_MEKANIK => 'Mekanik',
            self::TYPE_OPERATOR => 'Operator',
            self::TYPE_SHE => 'SHE',
            self::TYPE_HR => 'HR',
            default => $type ? ucfirst($type) : '-',
        };
    }

    public static function typeBadgeClasses(?string $type): string
    {
        return match ($type) {
            self::TYPE_MEKANIK => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::TYPE_OPERATOR => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::TYPE_SHE => 'bg-cyan-50 text-cyan-700 ring-cyan-200',
            self::TYPE_HR => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-gray-50 text-gray-700 ring-gray-200',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function levelOptions(): array
    {
        return collect(self::LEVELS_BY_TYPE)
            ->flatMap(fn (array $levels): array => $levels)
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function levelsFor(?string $type): array
    {
        return self::LEVELS_BY_TYPE[$type] ?? [];
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
