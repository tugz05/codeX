<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\AssignmentSubmission;
use App\Models\ExamAttempt;
use App\Models\QuizAttempt;
use App\Models\Student\ClassListUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentGradesController extends Controller
{
    /**
     * Display all grades across all enrolled classes
     */
    public function index()
    {
        $userId = Auth::id();

        // Get enrolled classes
        $enrolledClasses = ClassListUser::with(['classlist.section'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        $classGrades = [];

        foreach ($enrolledClasses as $enrollment) {
            $classlist = $enrollment->classlist;
            $classlistId = $classlist->id;

            // Get all grades for this class
            $grades = collect();

            // Activity submissions
            $activitySubmissions = ActivitySubmission::with(['activity'])
                ->where('classlist_id', $classlistId)
                ->where('user_id', $userId)
                ->whereNotNull('score')
                ->where('status', 'graded')
                ->get()
                ->map(function ($submission) {
                    $percentage = (float) ($submission->activity->points > 0 
                        ? round(($submission->score / $submission->activity->points) * 100, 2) 
                        : 0);
                    return [
                        'id' => $submission->id,
                        'type' => 'activity',
                        'title' => $submission->activity->title,
                        'score' => $submission->score,
                        'points' => $submission->activity->points,
                        'percentage' => $percentage,
                        'submitted_at' => $submission->submitted_at?->toIso8601String(),
                        'graded_at' => $submission->updated_at->toIso8601String(),
                    ];
                });

            // Assignment submissions
            $assignmentSubmissions = AssignmentSubmission::with(['assignment'])
                ->where('classlist_id', $classlistId)
                ->where('user_id', $userId)
                ->whereNotNull('score')
                ->where('status', 'graded')
                ->get()
                ->map(function ($submission) {
                    $percentage = (float) ($submission->assignment->points > 0 
                        ? round(($submission->score / $submission->assignment->points) * 100, 2) 
                        : 0);
                    return [
                        'id' => $submission->id,
                        'type' => 'assignment',
                        'title' => $submission->assignment->title,
                        'score' => $submission->score,
                        'points' => $submission->assignment->points,
                        'percentage' => $percentage,
                        'submitted_at' => $submission->submitted_at?->toIso8601String(),
                        'graded_at' => $submission->updated_at->toIso8601String(),
                    ];
                });

            // Quiz attempts
            $quizAttempts = QuizAttempt::with(['quiz'])
                ->where('classlist_id', $classlistId)
                ->where('user_id', $userId)
                ->whereNotNull('score')
                ->where('status', 'submitted')
                ->get()
                ->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'type' => 'quiz',
                        'title' => $attempt->quiz->title,
                        'score' => $attempt->score,
                        'points' => $attempt->total_points,
                        'percentage' => (float) ($attempt->percentage ?? ($attempt->total_points > 0 
                            ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                            : 0)),
                        'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                        'graded_at' => $attempt->submitted_at?->toIso8601String(),
                    ];
                });

            // Exam attempts
            $examAttempts = ExamAttempt::with(['examination'])
                ->where('classlist_id', $classlistId)
                ->where('user_id', $userId)
                ->whereNotNull('score')
                ->where('status', 'submitted')
                ->get()
                ->map(function ($attempt) {
                    return [
                        'id' => $attempt->id,
                        'type' => 'examination',
                        'title' => $attempt->examination->title,
                        'score' => $attempt->score,
                        'points' => $attempt->total_points,
                        'percentage' => (float) ($attempt->percentage ?? ($attempt->total_points > 0 
                            ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                            : 0)),
                        'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                        'graded_at' => $attempt->submitted_at?->toIso8601String(),
                    ];
                });

            $allGrades = $activitySubmissions
                ->concat($assignmentSubmissions)
                ->concat($quizAttempts)
                ->concat($examAttempts)
                ->sortByDesc('graded_at')
                ->values();

            // Calculate class statistics
            $totalPoints = $allGrades->sum('points');
            $earnedPoints = $allGrades->sum('score');
            $averagePercentage = (float) ($allGrades->count() > 0 
                ? round($allGrades->avg('percentage'), 2) 
                : 0);
            $overallPercentage = (float) ($totalPoints > 0 
                ? round(($earnedPoints / $totalPoints) * 100, 2) 
                : 0);

            // Grade history (last 10 grades by date)
            $gradeHistory = $allGrades
                ->take(10)
                ->map(fn($grade) => [
                    'date' => $grade['graded_at'],
                    'percentage' => $grade['percentage'],
                ])
                ->values();

            $classGrades[] = [
                'classlist' => [
                    'id' => $classlist->id,
                    'name' => $classlist->name,
                    'academic_year' => $classlist->academic_year,
                    'section' => $classlist->section ? [
                        'id' => $classlist->section->id,
                        'name' => $classlist->section->name,
                    ] : null,
                ],
                'grades' => $allGrades,
                'statistics' => [
                    'total_items' => $allGrades->count(),
                    'total_points' => $totalPoints,
                    'earned_points' => $earnedPoints,
                    'average_percentage' => $averagePercentage,
                    'overall_percentage' => $overallPercentage,
                ],
                'grade_history' => $gradeHistory,
            ];
        }

        // Overall statistics across all classes
        $allGradesFlat = collect($classGrades)->pluck('grades')->flatten(1);
        $overallStats = [
            'total_classes' => count($classGrades),
            'total_items' => $allGradesFlat->count(),
            'total_points' => $allGradesFlat->sum('points'),
            'earned_points' => $allGradesFlat->sum('score'),
            'average_percentage' => (float) ($allGradesFlat->count() > 0 
                ? round($allGradesFlat->avg('percentage'), 2) 
                : 0),
            'overall_percentage' => (float) ($allGradesFlat->sum('points') > 0 
                ? round(($allGradesFlat->sum('score') / $allGradesFlat->sum('points')) * 100, 2) 
                : 0),
        ];

        // Grade distribution
        $distribution = $this->calculateGradeDistribution($allGradesFlat);

        return Inertia::render('Student/Grades/Index', [
            'class_grades' => $classGrades,
            'overall_stats' => $overallStats,
            'distribution' => $distribution,
        ]);
    }

    /**
     * Calculate grade distribution
     */
    private function calculateGradeDistribution($grades): array
    {
        $distribution = [
            'A' => 0,  // 90-100
            'B' => 0,  // 80-89
            'C' => 0,  // 70-79
            'D' => 0,  // 60-69
            'F' => 0,  // 0-59
        ];

        foreach ($grades as $grade) {
            $percentage = $grade['percentage'] ?? 0;
            if ($percentage >= 90) {
                $distribution['A']++;
            } elseif ($percentage >= 80) {
                $distribution['B']++;
            } elseif ($percentage >= 70) {
                $distribution['C']++;
            } elseif ($percentage >= 60) {
                $distribution['D']++;
            } else {
                $distribution['F']++;
            }
        }

        return $distribution;
    }
}
