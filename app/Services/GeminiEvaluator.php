<?php
// app/Services/GeminiEvaluator.php

namespace App\Services;

use \Log;
use GuzzleHttp\Client;

class GeminiEvaluator
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $model = null,
    ) {
        $this->apiKey = $this->apiKey ?? config('services.google.ai.key', env('GOOGLE_API_KEY'));
        $this->model  = $this->model  ?? env('GEMINI_MODEL', 'gemini-2.0-flash');
    }

    public function evaluate(array $payload): array
    {
        $client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/v1beta/',
            'timeout'  => 25,
        ]);
        $system = $this->systemPrompt();
        $user   = $this->userPrompt($payload);

        Log::info('System Prompt:', ['prompt' => $system]);
        Log::info('User Prompt:', ['prompt' => $user]);

        try {
            $res = $client->post("models/{$this->model}:generateContent", [
                'query' => ['key' => $this->apiKey],
                'json'  => [
                    'contents' => [[
                        'role'  => 'user',
                        'parts' => [
                            ['text' => $system],
                            ['text' => $user],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'maxOutputTokens' => 1024,
                        'responseMimeType' => 'application/json',
                    ],
                ],
            ]);

            $data = json_decode((string) $res->getBody(), true);
            $jsonText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsed   = json_decode($jsonText, true);

            if (!is_array($parsed) || !array_key_exists('criteria', $parsed)) {
                return ['ok'=>false,'error'=>'Invalid JSON from model','raw'=>$data];
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
            }, $parsed['criteria'] ?? []);            // Calculate total points and score
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
            return ['ok'=>false,'error'=>$e->getMessage()];
        }
    }

    private function systemPrompt(): string
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

    private function userPrompt(array $p): string
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
