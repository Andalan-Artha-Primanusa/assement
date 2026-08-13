<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewAspect extends Model
{
    protected $fillable = [
        'interview_category_id',
        'name',
        'weight',
        'order',
    ];

    public function category()
    {
        return $this->belongsTo(InterviewCategory::class, 'interview_category_id');
    }
}
