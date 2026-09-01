<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all users assigned to this site.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'site', 'code');
    }

    /**
     * Check if this site is the Head Office (HO).
     */
    public function isHO(): bool
    {
        return strtoupper($this->code) === 'HO';
    }

    /**
     * Scope to only active sites.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to exclude HO from dropdown for peserta.
     */
    public function scopeExcludeHO($query)
    {
        return $query->where('code', '<>', 'HO');
    }
}
