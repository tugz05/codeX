<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'examination_id',
        'user_id',
        'classlist_id',
        'attempt_number',
        'score',
        'total_points',
        'percentage',
        'status',
        'started_at',
        'submitted_at',
        'time_spent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'attempt_number' => 'integer',
        'score' => 'integer',
        'total_points' => 'integer',
        'percentage' => 'decimal:2',
        'time_spent' => 'integer',
    ];

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classlist(): BelongsTo
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    public function answers(): MorphMany
    {
        return $this->morphMany(QuestionAnswer::class, 'attemptable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(AttemptActivity::class, 'attemptable');
    }

    // Calculate score based on answers
    public function calculateScore(): void
    {
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($this->answers as $answer) {
            if ($answer->question) {
                $totalPoints += $answer->question->points;
                $earnedPoints += $answer->points_earned ?? 0;
            }
        }

        $this->total_points = $totalPoints;
        $this->score = $earnedPoints;
        $this->percentage = $totalPoints > 0 
            ? round(($earnedPoints / $totalPoints) * 100, 2) 
            : 0;
    }
}
