<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewAssessment extends Model
{
    protected $fillable = [
        'interview_template_id',
        'candidate_name',
        'job_title',
        'gender',
        'department',
        'age',
        'location',
        'domicile',
        'join_date',
        'expected_salary',
        'interview_date',
        'total_score',
        'average_score',
        'percentage',
        'recommendation',
        'hr_conclusion',
        'hr_interviewer_name',
        'user_interviewer_name',
        'signature_path',
        'photos',
        'created_by',
    ];

    protected $casts = [
        'join_date' => 'date',
        'interview_date' => 'date',
        'total_score' => 'decimal:2',
        'average_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'photos' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(InterviewTemplate::class, 'interview_template_id');
    }

    public function scores()
    {
        return $this->hasMany(InterviewScore::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
