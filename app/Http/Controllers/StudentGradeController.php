<?php
// app/Http/Controllers/StudentGradeController.php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Classlist;
use App\Models\Criteria;
use App\Services\NotificationService;
use App\Services\OpenAIEvaluator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentGradeController extends Controller
{
    protected function ensureEnrolled(Classlist $classlist): void
    {
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403, 'Not enrolled in this class.');
    }

    /**
     * POST: run AI grading on the student's latest submission for this activity.
     */
    public function grade(Request $request, Classlist $classlist, Activity $activity)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($activity->classlist_id === $classlist->id, 404);

        $studentId = Auth::id();

        $submission = ActivitySubmission::forUser($studentId)
            ->forActivity($activity->id)
            ->latestFirst()
            ->firstOrFail();

        // Load criteria selected for this activity
        // Assumes you have Activity::criteria() belongsToMany(Criteria::class, 'activity_criteria')
        // Return ONLY the criteria_items rows (flattened) for this activity
        // Get activity criteria with their assigned points
        $activityCriteria = $activity->criteria()
            ->select('criterias.*', 'activity_criteria.assigned_points')
            ->with([
                'items' => function ($q) {
                    $q->select(
                        'id',
                        'criteria_id',
                        'label',
                        'description',
                        'weight',
                        'sort_order'
                    )->orderBy('sort_order');
                }
            ])
            ->get();

        // Extract criteria items with their assigned points from the activity
        $criteria = $activityCriteria
            ->flatMap(function ($set) {
                return $set->items->map(function ($item) use ($set) {
                    $item->assigned_points = $set->assigned_points;
                    return $item;
                });
            })
            ->values()
            ->all();

        Log::info('Criteria for grading:', ['criteria' => $criteria]);
        // Runtime artifacts if you store them on submission (optional)
        $runtime = [
            'stdout'   => (string) ($submission->stdout ?? ''),
            'stderr'   => (string) ($submission->stderr ?? ''),
            'exit'     => (int) ($submission->exit_code ?? 0),
            'time_ms'  => (int) ($submission->runtime_ms ?? 0),
        ];

        // Calculate points for each criterion based on their weights and activity total points
        $totalWeight = collect($criteria)->sum('weight');
        $criteriaWithPoints = collect($criteria)->map(function ($item) use ($activity, $totalWeight) {
            $pointsForThisCriteria = $totalWeight > 0
                ? round(($item->weight / $totalWeight) * $activity->points)
                : 0;

            return [
                'id' => $item->id,
                'criteria_id' => $item->criteria_id,
                'label' => $item->label,
                'description' => $item->description,
                'weight' => $item->weight,
                'points' => $pointsForThisCriteria,
                'sort_order' => $item->sort_order
            ];
        })->all();

        $payload = [
            'language' => $submission->language,
            'code'     => $submission->code,
            'criteria' => $criteriaWithPoints,
            'total_points' => $activity->points,
            'runtime'  => $runtime,
            // 'tests' => [...], // plug in your test results here if available
        ];

        $evaluator = new OpenAIEvaluator();
        $result = $evaluator->evaluate($payload);

        if (!$result['ok']) {
            return back()->with('error', $result['error'] ?? 'AI evaluation failed.');
        }

        DB::transaction(function () use ($submission, $result, $activity, $classlist) {
            $submission->aiEvaluations()->create([
                'criteria_breakdown' => $result['criteria_breakdown'],
                'score'              => $result['score'],
                'feedback'           => $result['feedback'],
                'model_raw'          => $result['raw'],
            ]);

            // Update submission with final score/feedback
            $submission->update([
                'status'   => 'graded',
                'score'    => $result['score'],
                'feedback' => $result['feedback'],
            ]);

            // Send grade release notification
            $notificationService = app(NotificationService::class);
            $student = $submission->user;
            $actionUrl = route('student.activities.show', [$classlist->id, $activity->id], false);
            $message = "Your submission for '{$activity->title}' has been graded. Score: {$result['score']}/{$activity->points}";
            
            // In-app notification
            $notificationService->sendNotification(
                'grade_released',
                [$student],
                $activity->title,
                $message,
                ActivitySubmission::class,
                $submission->id,
                $classlist->id,
                $actionUrl
            );
            
            // Email notification
            $notificationService->sendEmailNotification(
                'grade_released',
                $student,
                $activity->title,
                $message,
                url($actionUrl)
            );
        });

        return back()->with('success', 'Graded by AI.');
    }

    /**
     * GET: latest evaluation JSON for current user’s latest submission (for AJAX in the UI).
     */
    public function latest(Request $request, Classlist $classlist, Activity $activity)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($activity->classlist_id === $classlist->id, 404);

        $studentId = Auth::id();

        $submission = ActivitySubmission::forUser($studentId)
            ->forActivity($activity->id)
            ->latestFirst()
            ->with('latestAiEvaluation')
            ->first();

        if (!$submission || !$submission->latestAiEvaluation) {
            return response()->json(['ok' => true, 'evaluation' => null]);
        }

        return response()->json([
            'ok' => true,
            'evaluation' => [
                'score' => $submission->latestAiEvaluation->score,
                'feedback' => $submission->latestAiEvaluation->feedback,
                'criteria_breakdown' => $submission->latestAiEvaluation->criteria_breakdown,
            ]
        ]);
    }
}
