<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewCategory extends Model
{
    protected $fillable = [
        'interview_template_id',
        'name',
        'order',
    ];

    public function template()
    {
        return $this->belongsTo(InterviewTemplate::class, 'interview_template_id');
    }

    public function aspects()
    {
        return $this->hasMany(InterviewAspect::class)->orderBy('order');
    }
}
