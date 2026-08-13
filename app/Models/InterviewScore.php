<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewScore extends Model
{
    protected $fillable = [
        'interview_assessment_id',
        'interview_aspect_id',
        'score',
        'notes',
    ];

    public function assessment()
    {
        return $this->belongsTo(InterviewAssessment::class, 'interview_assessment_id');
    }

    public function aspect()
    {
        return $this->belongsTo(InterviewAspect::class, 'interview_aspect_id');
    }
}
