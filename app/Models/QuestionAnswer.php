<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuestionAnswer extends Model
{
    protected $fillable = [
        'question_id',
        'attemptable_id',
        'attemptable_type',
        'answer',
        'is_correct',
        'points_earned',
        'feedback',
    ];

    protected $casts = [
        'answer' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function attemptable(): MorphTo
    {
        return $this->morphTo();
    }

    // Auto-grade the answer
    public function grade(): void
    {
        if (!$this->question) {
            $this->load('question');
        }

        if (!$this->question) {
            return;
        }

        // Use AI evaluation for essay questions
        if ($this->question->type === 'essay' || $this->question->type === 'short_answer') {
            $this->gradeWithAI();
            return;
        }

        // Use standard grading for other question types
        $isCorrect = $this->question->checkAnswer($this->answer);
        $this->is_correct = $isCorrect;
        $this->points_earned = $isCorrect ? $this->question->points : 0;
    }

    /**
     * Grade essay/short answer questions using AI
     */
    private function gradeWithAI(): void
    {
        try {
            $evaluator = new \App\Services\OpenAIEvaluator();
            
            // Get user answer as string
            $userAnswer = is_array($this->answer) 
                ? implode(' ', $this->answer) 
                : (string) $this->answer;
            
            // Get correct answer/reference
            $correctAnswer = null;
            if ($this->question->correct_answer) {
                $correctAnswer = is_array($this->question->correct_answer)
                    ? implode(', ', $this->question->correct_answer)
                    : (string) $this->question->correct_answer;
            }

            $result = $evaluator->evaluateEssay(
                $this->question->question_text,
                $userAnswer,
                $correctAnswer,
                $this->question->points,
                $this->question->explanation
            );

            if ($result['ok']) {
                $this->points_earned = $result['points_earned'];
                $this->feedback = $result['feedback'];
                // For essay questions, consider them "correct" if they score above 50%
                $this->is_correct = $result['percentage'] >= 50;
            } else {
                // Fallback to manual grading if AI fails
                $this->points_earned = 0;
                $this->feedback = $result['feedback'] ?? 'Answer requires manual review.';
                $this->is_correct = false;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI grading error: ' . $e->getMessage());
            // Fallback to manual grading
            $this->points_earned = 0;
            $this->feedback = 'Answer requires manual review due to evaluation error.';
            $this->is_correct = false;
        }
    }
}
