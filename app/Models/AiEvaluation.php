<?php
// app/Models/AiEvaluation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'criteria_breakdown',
        'score',
        'feedback',
        'model_raw',
    ];

    protected $casts = [
        'criteria_breakdown' => 'array',
        'model_raw' => 'array',
        'score' => 'integer',
    ];

    public function submission()
    {
        return $this->belongsTo(ActivitySubmission::class, 'submission_id');
    }
}
