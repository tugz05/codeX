<?php
// app/Services/OpenAIEvaluator.php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class OpenAIEvaluator
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $model = null,
    ) {
        $this->apiKey = $this->apiKey ?? config('services.openai.key', env('OPENAI_API_KEY'));
        $this->model  = $this->model  ?? env('OPENAI_MODEL', 'gpt-4o-mini');
    }

    /**
     * Evaluate an essay/short answer question
     * 
     * @param string $questionText The question text
     * @param string $userAnswer The student's answer
     * @param string|null $correctAnswer Reference answer(s) for comparison
     * @param int $maxPoints Maximum points for this question
     * @param string|null $explanation Expected explanation or rubric
     * @return array ['ok' => bool, 'score' => int, 'points_earned' => int, 'feedback' => string, 'percentage' => int]
     */
    public function evaluateEssay(string $questionText, string $userAnswer, ?string $correctAnswer = null, int $maxPoints = 10, ?string $explanation = null): array
    {
        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $systemPrompt = $this->systemPrompt();
        $userPrompt = $this->userPrompt($questionText, $userAnswer, $correctAnswer, $maxPoints, $explanation);

        Log::info('OpenAI Evaluation Request:', [
            'question' => $questionText,
            'user_answer_length' => strlen($userAnswer),
        ]);

        try {
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                    'response_format' => ['type' => 'json_object'],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $content = $data['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            if (!is_array($parsed) || !isset($parsed['score_percentage'])) {
                Log::error('Invalid OpenAI response', ['response' => $parsed]);
                return [
                    'ok' => false,
                    'error' => 'Invalid response from AI',
                    'score' => 0,
                    'points_earned' => 0,
                    'feedback' => 'Unable to evaluate answer. Please contact your instructor.',
                    'percentage' => 0,
                ];
            }

            $percentage = max(0, min(100, (int) $parsed['score_percentage']));
            $pointsEarned = round(($percentage / 100) * $maxPoints);
            $feedback = $parsed['feedback'] ?? 'No feedback provided.';

            return [
                'ok' => true,
                'score' => $pointsEarned,
                'points_earned' => $pointsEarned,
                'feedback' => $feedback,
                'percentage' => $percentage,
                'raw' => $parsed,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI evaluation error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            
            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'score' => 0,
                'points_earned' => 0,
                'feedback' => 'Error evaluating answer. Please contact your instructor.',
                'percentage' => 0,
            ];
        }
    }

    /**
     * Evaluate code submission (for activities)
     */
    public function evaluate(array $payload): array
    {
        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'timeout'  => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);

        $systemPrompt = $this->systemPromptForCode();
        $userPrompt = $this->userPromptForCode($payload);

        Log::info('OpenAI Code Evaluation Request:', [
            'language' => $payload['language'] ?? 'unknown',
        ]);

        try {
            $response = $client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userPrompt,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 2000,
                    'response_format' => ['type' => 'json_object'],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $content = $data['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($content, true);

            if (!is_array($parsed) || !array_key_exists('criteria', $parsed)) {
                return ['ok' => false, 'error' => 'Invalid JSON from model', 'raw' => $data];
            }

            $overallPercentage = isset($parsed['overall_percentage'])
                ? max(0, min(100, (int) $parsed['overall_percentage']))
                : null;

            // Map criteria by ID for easy lookup
            $criteriaMap = collect($payload['criteria'])->keyBy('id');

            $breakdown = array_map(function ($c) use ($criteriaMap) {
                $criteriaId = $c['id'] ?? null;
                $percentage = max(0, min(100, (int) ($c['score_percentage'] ?? 0)));

                // Find the matching criteria item
                $criteriaItem = $criteriaMap->get($criteriaId);
                $maxPoints = $criteriaItem['points'] ?? 0;

                // Calculate actual score based on percentage achievement
                $actualScore = round(($percentage / 100) * $maxPoints);

                return [
                    'id'         => $criteriaId,
                    'name'       => $criteriaItem['label'] ?? $c['name'] ?? '',
                    'percentage' => $percentage,
                    'points'     => $maxPoints,
                    'score'      => $actualScore,
                    'comment'    => $c['comment'] ?? '',
                ];
            }, $parsed['criteria'] ?? []);

            // Calculate total points and score
            $totalPoints = array_sum(array_column($breakdown, 'points'));
            $totalScore = array_sum(array_column($breakdown, 'score'));

            return [
                'ok' => true,
                'score' => $totalScore,
                'total_points' => $totalPoints,
                'percentage' => $overallPercentage,
                'criteria_breakdown' => $breakdown,
                'feedback' => $parsed['feedback'] ?? '',
                'raw' => $parsed,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an expert academic evaluator. Your task is to evaluate student essay/short answer responses and provide fair, constructive feedback.

Return STRICT JSON only (no markdown), matching this exact schema:

{
  "score_percentage": 0-100,  // Overall quality score as a percentage
  "feedback": "Brief, constructive feedback for the student"
}

Evaluation Criteria:
- Accuracy and correctness of the answer
- Completeness of the response
- Clarity and organization of ideas
- Use of appropriate terminology and concepts
- Depth of understanding demonstrated

Be fair but rigorous. Provide specific, actionable feedback that helps the student improve.
PROMPT;
    }

    private function userPrompt(string $questionText, string $userAnswer, ?string $correctAnswer, int $maxPoints, ?string $explanation): string
    {
        $prompt = "Question: {$questionText}\n\n";
        $prompt .= "Student Answer: {$userAnswer}\n\n";
        $prompt .= "Maximum Points: {$maxPoints}\n\n";
        
        if ($correctAnswer) {
            $prompt .= "Reference Answer/Key Points: {$correctAnswer}\n\n";
        }
        
        if ($explanation) {
            $prompt .= "Expected Explanation/Rubric: {$explanation}\n\n";
        }
        
        $prompt .= "Evaluate the student's answer and provide a score (0-100%) and constructive feedback.\n";
        $prompt .= "Return JSON with 'score_percentage' and 'feedback' fields.";

        return $prompt;
    }

    private function systemPromptForCode(): string
    {
        return <<<PROMPT
You are an expert programming evaluator. Grade student code against a dynamic rubric.
Return STRICT JSON only (no markdown), matching:

{
  "criteria": [
    {
      "id": "...",
      "name": "...",
      "score_percentage": 0-100,  // percentage achievement for this criterion
      "comment": "..."
    }
  ],
  "overall_percentage": 0-100,  // overall achievement percentage
  "feedback": "short summary for the student"
}

Rules:
- Evaluate each criterion on a 0-100% scale based on code quality and correctness
- "score_percentage" represents how well the criterion was met (0-100%)
- "overall_percentage" = average of all criteria percentages
- Use runtime artifacts (stdout, stderr, exit code, time) & tests if provided
- Be concise, specific, and actionable in "comment" and "feedback"
PROMPT;
    }

    private function userPromptForCode(array $p): string
    {
        $criteriaJson = json_encode($p['criteria'], JSON_PRETTY_PRINT);
        $testsJson    = json_encode($p['tests'] ?? [], JSON_PRETTY_PRINT);
        $runtimeJson  = json_encode($p['runtime'] ?? [], JSON_PRETTY_PRINT);

        $totalPoints = $p['total_points'] ?? 100;
        return <<<TXT
Language: {$p['language']}

Total Activity Points: {$totalPoints}

Student Code:
<<<CODE
{$p['code']}
CODE

Evaluation Criteria:
{$criteriaJson}

IMPORTANT: Score each criterion purely as a PERCENTAGE (0-100%) of how well it meets the requirements.
The final score for each criterion will be calculated based on its allocated points.

Runtime Information:
{$runtimeJson}

Test Results:
{$testsJson}

Return JSON matching the schema. Each criterion must be evaluated as a percentage (0-100%) based on quality and correctness.
TXT;
    }
}
