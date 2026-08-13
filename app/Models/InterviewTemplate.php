<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'min_recommended_percentage',
        'min_considered_percentage',
        'is_active',
    ];

    public function categories()
    {
        return $this->hasMany(InterviewCategory::class)->orderBy('order');
    }

    public function assessments()
    {
        return $this->hasMany(InterviewAssessment::class);
    }
}
