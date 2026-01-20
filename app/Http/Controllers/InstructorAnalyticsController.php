<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\Classlist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InstructorAnalyticsController extends Controller
{
    public function index()
    {
        return Inertia::render('Instructor/Analytics/Index');
    }

    public function data()
    {
        try {
            $instructorId = Auth::id();

            if (!$instructorId) {
                throw new \Exception('User not authenticated');
            }

            // Submissions over time - using actual submission dates
            $submissionTrends = ActivitySubmission::whereNotNull('submitted_at')
                ->whereHas('classlist', function ($query) use ($instructorId) {
                    $query->where('user_id', $instructorId);
                })
                ->select(
                    DB::raw('DATE(submitted_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy(DB::raw('DATE(submitted_at)'))
                ->orderBy('date')
                ->get();

            // Class performance distribution - using raw scores without points
            $classPerformance = Classlist::where('user_id', $instructorId)
                ->withCount('students')
                ->with(['activities' => function($query) {
                    $query->withCount(['submissions as submission_count' => function($query) {
                        $query->where('status', 'graded')
                            ->whereNotNull('score');
                    }]);
                }])
                ->get()
                ->map(function($class) {
                    $totalScore = 0;
                    $totalSubmissions = 0;

                    foreach ($class->activities as $activity) {
                        $totalScore += $activity->submission_count;
                        $totalSubmissions++;
                    }

                    return [
                        'id' => $class->id,
                        'name' => $class->name,
                        'students_count' => $class->students_count,
                        'average_score' => $totalSubmissions > 0 ? ($totalScore / $totalSubmissions) : 0
                    ];
                });

            // Student performance distribution - using raw scores
            $studentPerformance = User::whereHas('enrolledClasses', function ($query) use ($instructorId) {
                $query->where('classlists.user_id', $instructorId);
            })
            ->withCount(['submissions as total_submissions' => function($query) {
                $query->where('status', 'graded');
            }])
            ->withAvg(['submissions as average_score' => function ($query) {
                $query->where('status', 'graded')
                    ->whereNotNull('score');
            }], 'score')
            ->get()
            ->groupBy(function ($student) {
                if ($student->total_submissions === 0) return 'No submissions';
                $score = $student->average_score ?? 0;
                if ($score >= 90) return '90-100%';
                if ($score >= 80) return '80-89%';
                if ($score >= 70) return '70-79%';
                if ($score >= 60) return '60-69%';
                return 'Below 60%';
            })
            ->map->count();

            // Activity completion rates - using status only
            $activityCompletionRates = ActivitySubmission::whereHas('classlist', function ($query) use ($instructorId) {
                $query->where('user_id', $instructorId);
            })
            ->select(
                'activity_id',
                DB::raw('COUNT(*) as total_submissions'),
                DB::raw('SUM(CASE WHEN status IN ("submitted", "graded") THEN 1 ELSE 0 END) as completed_count')
            )
            ->groupBy('activity_id')
            ->get()
            ->map(function ($activity) {
                return [
                    'activity_id' => $activity->activity_id,
                    'completion_rate' => $activity->total_submissions > 0
                        ? ($activity->completed_count / $activity->total_submissions) * 100
                        : 0
                ];
            });

            return response()->json([
                'submissionTrends' => $submissionTrends,
                'classPerformance' => $classPerformance,
                'studentPerformance' => $studentPerformance,
                'activityCompletionRates' => $activityCompletionRates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
