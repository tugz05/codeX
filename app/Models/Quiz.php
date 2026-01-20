<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quiz extends Model
{
    protected $fillable = [
        'classlist_id',
        'created_by',
        'title',
        'description',
        'total_points',
        'time_limit',
        'attempts_allowed',
        'shuffle_questions',
        'show_correct_answers',
        'is_published',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'shuffle_questions' => 'boolean',
        'show_correct_answers' => 'boolean',
        'is_published' => 'boolean',
        'time_limit' => 'integer',
        'attempts_allowed' => 'integer',
        'total_points' => 'integer',
    ];

    public function classlist(): BelongsTo
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tests(): MorphMany
    {
        return $this->morphMany(Test::class, 'testable')->orderBy('order');
    }

    public function questions(): MorphMany
    {
        return $this->morphMany(Question::class, 'questionable')->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function userAttempts($userId): HasMany
    {
        return $this->hasMany(QuizAttempt::class)->where('user_id', $userId);
    }
}
