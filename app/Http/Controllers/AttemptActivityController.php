<?php

namespace App\Http\Controllers;

use App\Models\AttemptActivity;
use App\Models\QuizAttempt;
use App\Models\ExamAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttemptActivityController extends Controller
{
    /**
     * Log an activity for a quiz or exam attempt
     */
    public function log(Request $request)
    {
        $data = $request->validate([
            'attemptable_id' => ['required', 'integer'],
            'attemptable_type' => ['required', 'string', 'in:App\\Models\\QuizAttempt,App\\Models\\ExamAttempt'],
            'activity_type' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ]);

        // Verify the attempt belongs to the current user
        $attempt = $data['attemptable_type'] === 'App\\Models\\QuizAttempt'
            ? QuizAttempt::find($data['attemptable_id'])
            : ExamAttempt::find($data['attemptable_id']);

        if (!$attempt || $attempt->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Allow logging activities even after submission to ensure all activities are captured
        // This is important for the "exam_ended" activity that may be logged during submission
        if ($attempt->status !== 'in_progress') {
            // Log info but don't block - this allows final activities to be logged during submission
            Log::info('Attempt activity logged for non-in-progress attempt', [
                'attempt_id' => $attempt->id,
                'status' => $attempt->status,
                'activity_type' => $data['activity_type'],
            ]);
        }

        $activity = AttemptActivity::create([
            'attemptable_id' => $data['attemptable_id'],
            'attemptable_type' => $data['attemptable_type'],
            'activity_type' => $data['activity_type'],
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? [],
            'occurred_at' => now(),
        ]);

        Log::info('Attempt activity logged', [
            'activity_id' => $activity->id,
            'attemptable_type' => $data['attemptable_type'],
            'attemptable_id' => $data['attemptable_id'],
            'activity_type' => $data['activity_type'],
        ]);

        return response()->json(['success' => true, 'activity_id' => $activity->id]);
    }
}
