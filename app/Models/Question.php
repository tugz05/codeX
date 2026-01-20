<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Question extends Model
{
    protected $fillable = [
        'test_id',
        'questionable_id',
        'questionable_type',
        'question_text',
        'type',
        'points',
        'order',
        'options',
        'correct_answer',
        'explanation',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'points' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function questionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function answers(): MorphMany
    {
        return $this->morphMany(QuestionAnswer::class, 'attemptable');
    }

    // Helper method to check if answer is correct
    public function checkAnswer($userAnswer): bool
    {
        if (!$this->correct_answer) {
            return false;
        }

        $correctAnswers = is_array($this->correct_answer) 
            ? $this->correct_answer 
            : [$this->correct_answer];

        $userAnswers = is_array($userAnswer) 
            ? $userAnswer 
            : [$userAnswer];

        // Normalize answers (trim, lowercase for comparison)
        $correctNormalized = array_map(function($ans) {
            return strtolower(trim($ans));
        }, $correctAnswers);

        $userNormalized = array_map(function($ans) {
            return strtolower(trim($ans));
        }, $userAnswers);

        // For multiple choice, check if arrays match
        if ($this->type === 'multiple_choice' || $this->type === 'true_false') {
            sort($correctNormalized);
            sort($userNormalized);
            return $correctNormalized === $userNormalized;
        }

        // For short answer, check if any correct answer matches
        foreach ($userNormalized as $userAns) {
            if (in_array($userAns, $correctNormalized)) {
                return true;
            }
        }

        return false;
    }
}
