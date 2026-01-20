<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classlist;
use App\Models\Examination;
use App\Models\ExamAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student\ClassListUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $now = Carbon::now();

        // Get enrolled classes
        $enrolledClasses = ClassListUser::with(['classlist'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get()
            ->map(function ($enrollment) {
                $classlist = $enrollment->classlist;
                return [
                    'id' => $classlist->id,
                    'name' => $classlist->name,
                    'academic_year' => $classlist->academic_year,
                    'room' => $classlist->room,
                    'section' => $classlist->section,
                    'joined_at' => $enrollment->joined_at?->toIso8601String(),
                ];
            });

        $classlistIds = $enrolledClasses->pluck('id')->toArray();

        // Get upcoming deadlines (next 7 days)
        $upcomingDeadlines = collect();

        // Activities with due dates
        $activities = Activity::whereIn('classlist_id', $classlistIds)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $now->toDateString())
            ->where('due_date', '<=', $now->copy()->addDays(7)->toDateString())
            ->with('classlist')
            ->get()
            ->map(function ($activity) use ($userId) {
                $dueDateTime = $this->combineDateTime($activity->due_date, $activity->due_time);
                $submission = ActivitySubmission::where('activity_id', $activity->id)
                    ->where('user_id', $userId)
                    ->latest()
                    ->first();
                
                return [
                    'id' => $activity->id,
                    'type' => 'activity',
                    'title' => $activity->title,
                    'classlist' => [
                        'id' => $activity->classlist->id,
                        'name' => $activity->classlist->name,
                    ],
                    'due_date' => $activity->due_date?->format('Y-m-d'),
                    'due_time' => $activity->due_time,
                    'due_datetime' => $dueDateTime?->toIso8601String(),
                    'points' => $activity->points,
                    'submitted' => $submission && in_array($submission->status, ['submitted', 'graded']),
                    'status' => $submission?->status ?? 'pending',
                ];
            });

        // Assignments with due dates
        $assignments = Assignment::whereIn('classlist_id', $classlistIds)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $now->toDateString())
            ->where('due_date', '<=', $now->copy()->addDays(7)->toDateString())
            ->with('classlist')
            ->get()
            ->map(function ($assignment) use ($userId) {
                $dueDateTime = $this->combineDateTime($assignment->due_date, $assignment->due_time);
                $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('user_id', $userId)
                    ->latest()
                    ->first();
                
                return [
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'classlist' => [
                        'id' => $assignment->classlist->id,
                        'name' => $assignment->classlist->name,
                    ],
                    'due_date' => $assignment->due_date?->format('Y-m-d'),
                    'due_time' => $assignment->due_time,
                    'due_datetime' => $dueDateTime?->toIso8601String(),
                    'points' => $assignment->points,
                    'submitted' => $submission && in_array($submission->status, ['submitted', 'graded']),
                    'status' => $submission?->status ?? 'pending',
                ];
            });

        // Quizzes with end dates
        $quizzes = Quiz::whereIn('classlist_id', $classlistIds)
            ->where('is_published', true)
            ->whereNotNull('end_date')
            ->where('end_date', '>=', $now)
            ->where('end_date', '<=', $now->copy()->addDays(7))
            ->with('classlist')
            ->get()
            ->map(function ($quiz) use ($userId) {
                $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                    ->where('user_id', $userId)
                    ->latest()
                    ->first();
                
                return [
                    'id' => $quiz->id,
                    'type' => 'quiz',
                    'title' => $quiz->title,
                    'classlist' => [
                        'id' => $quiz->classlist->id,
                        'name' => $quiz->classlist->name,
                    ],
                    'due_date' => $quiz->end_date?->format('Y-m-d'),
                    'due_time' => $quiz->end_date?->format('H:i'),
                    'due_datetime' => $quiz->end_date?->toIso8601String(),
                    'points' => $quiz->total_points,
                    'submitted' => $attempt && $attempt->status === 'completed',
                    'status' => $attempt?->status ?? 'pending',
                ];
            });

        // Examinations with end dates
        $examinations = Examination::whereIn('classlist_id', $classlistIds)
            ->where('is_published', true)
            ->whereNotNull('end_date')
            ->where('end_date', '>=', $now)
            ->where('end_date', '<=', $now->copy()->addDays(7))
            ->with('classlist')
            ->get()
            ->map(function ($exam) use ($userId) {
                $attempt = ExamAttempt::where('examination_id', $exam->id)
                    ->where('user_id', $userId)
                    ->latest()
                    ->first();
                
                return [
                    'id' => $exam->id,
                    'type' => 'examination',
                    'title' => $exam->title,
                    'classlist' => [
                        'id' => $exam->classlist->id,
                        'name' => $exam->classlist->name,
                    ],
                    'due_date' => $exam->end_date?->format('Y-m-d'),
                    'due_time' => $exam->end_date?->format('H:i'),
                    'due_datetime' => $exam->end_date?->toIso8601String(),
                    'points' => $exam->total_points,
                    'submitted' => $attempt && $attempt->status === 'completed',
                    'status' => $attempt?->status ?? 'pending',
                ];
            });

        $upcomingDeadlines = $activities->concat($assignments)->concat($quizzes)->concat($examinations)
            ->sortBy('due_datetime')
            ->take(10)
            ->values();

        // Get recent grades and feedback (last 10)
        $recentGrades = collect();

        // Activity submissions with grades
        $activityGrades = ActivitySubmission::with(['activity.classlist', 'latestAiEvaluation'])
            ->whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereNotNull('score')
            ->whereIn('status', ['graded', 'submitted'])
            ->latest('submitted_at')
            ->take(5)
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'type' => 'activity',
                    'title' => $submission->activity->title,
                    'classlist' => [
                        'id' => $submission->activity->classlist->id,
                        'name' => $submission->activity->classlist->name,
                    ],
                    'score' => $submission->score,
                    'points' => $submission->activity->points,
                    'percentage' => $submission->activity->points > 0 
                        ? round(($submission->score / $submission->activity->points) * 100, 2) 
                        : 0,
                    'feedback' => $submission->feedback ?? $submission->latestAiEvaluation?->feedback,
                    'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    'graded_at' => $submission->updated_at->toIso8601String(),
                ];
            });

        // Assignment submissions with grades
        $assignmentGrades = AssignmentSubmission::with(['assignment.classlist'])
            ->whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereNotNull('score')
            ->whereIn('status', ['graded', 'submitted'])
            ->latest('submitted_at')
            ->take(5)
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'type' => 'assignment',
                    'title' => $submission->assignment->title,
                    'classlist' => [
                        'id' => $submission->assignment->classlist->id,
                        'name' => $submission->assignment->classlist->name,
                    ],
                    'score' => $submission->score,
                    'points' => $submission->assignment->points,
                    'percentage' => $submission->assignment->points > 0 
                        ? round(($submission->score / $submission->assignment->points) * 100, 2) 
                        : 0,
                    'feedback' => $submission->feedback,
                    'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    'graded_at' => $submission->updated_at->toIso8601String(),
                ];
            });

        // Quiz attempts with scores
        $quizGrades = QuizAttempt::with(['quiz.classlist'])
            ->whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereNotNull('score')
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'type' => 'quiz',
                    'title' => $attempt->quiz->title,
                    'classlist' => [
                        'id' => $attempt->quiz->classlist->id,
                        'name' => $attempt->quiz->classlist->name,
                    ],
                    'score' => $attempt->score,
                    'points' => $attempt->total_points,
                    'percentage' => $attempt->percentage ?? ($attempt->total_points > 0 
                        ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                        : 0),
                    'feedback' => null,
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'graded_at' => $attempt->submitted_at?->toIso8601String(),
                ];
            });

        // Exam attempts with scores
        $examGrades = ExamAttempt::with(['examination.classlist'])
            ->whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereNotNull('score')
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'type' => 'examination',
                    'title' => $attempt->examination->title,
                    'classlist' => [
                        'id' => $attempt->examination->classlist->id,
                        'name' => $attempt->examination->classlist->name,
                    ],
                    'score' => $attempt->score,
                    'points' => $attempt->total_points,
                    'percentage' => $attempt->percentage ?? ($attempt->total_points > 0 
                        ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                        : 0),
                    'feedback' => null,
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'graded_at' => $attempt->submitted_at?->toIso8601String(),
                ];
            });

        $recentGrades = $activityGrades->concat($assignmentGrades)->concat($quizGrades)->concat($examGrades)
            ->sortByDesc('graded_at')
            ->take(10)
            ->values();

        // Get pending submissions (not yet submitted)
        $pendingSubmissions = collect();

        // Pending activities
        $submittedActivityIds = ActivitySubmission::whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->pluck('activity_id')
            ->toArray();

        $pendingActivities = Activity::whereIn('classlist_id', $classlistIds)
            ->where(function ($q) use ($now) {
                $q->whereNull('due_date')
                    ->orWhere('due_date', '>=', $now->toDateString());
            })
            ->whereNotIn('id', $submittedActivityIds)
            ->with('classlist')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => 'activity',
                    'title' => $activity->title,
                    'classlist' => [
                        'id' => $activity->classlist->id,
                        'name' => $activity->classlist->name,
                    ],
                    'due_date' => $activity->due_date?->format('Y-m-d'),
                    'due_time' => $activity->due_time,
                    'points' => $activity->points,
                ];
            });

        // Pending assignments
        $submittedAssignmentIds = AssignmentSubmission::whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->pluck('assignment_id')
            ->toArray();

        $pendingAssignments = Assignment::whereIn('classlist_id', $classlistIds)
            ->where(function ($q) use ($now) {
                $q->whereNull('due_date')
                    ->orWhere('due_date', '>=', $now->toDateString());
            })
            ->whereNotIn('id', $submittedAssignmentIds)
            ->with('classlist')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'classlist' => [
                        'id' => $assignment->classlist->id,
                        'name' => $assignment->classlist->name,
                    ],
                    'due_date' => $assignment->due_date?->format('Y-m-d'),
                    'due_time' => $assignment->due_time,
                    'points' => $assignment->points,
                ];
            });

        $pendingSubmissions = $pendingActivities->concat($pendingAssignments)
            ->take(10)
            ->values();

        // Calculate progress statistics
        $totalActivities = Activity::whereIn('classlist_id', $classlistIds)->count();
        $totalAssignments = Assignment::whereIn('classlist_id', $classlistIds)->count();
        $totalQuizzes = Quiz::whereIn('classlist_id', $classlistIds)->where('is_published', true)->count();
        $totalExaminations = Examination::whereIn('classlist_id', $classlistIds)->where('is_published', true)->count();

        $submittedActivities = ActivitySubmission::whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->distinct('activity_id')
            ->count('activity_id');

        $submittedAssignments = AssignmentSubmission::whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->whereIn('status', ['submitted', 'graded'])
            ->distinct('assignment_id')
            ->count('assignment_id');

        $completedQuizzes = QuizAttempt::whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->distinct('quiz_id')
            ->count('quiz_id');

        $completedExams = ExamAttempt::whereIn('classlist_id', $classlistIds)
            ->where('user_id', $userId)
            ->where('status', 'submitted')
            ->distinct('examination_id')
            ->count('examination_id');

        $totalItems = $totalActivities + $totalAssignments + $totalQuizzes + $totalExaminations;
        $completedItems = $submittedActivities + $submittedAssignments + $completedQuizzes + $completedExams;
        $completionPercentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0;

        // Get recent activities (last 5 from all classes)
        $recentActivities = Activity::whereIn('classlist_id', $classlistIds)
            ->with('classlist')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => 'activity',
                    'title' => $activity->title,
                    'classlist' => [
                        'id' => $activity->classlist->id,
                        'name' => $activity->classlist->name,
                    ],
                    'created_at' => $activity->created_at->toIso8601String(),
                ];
            });

        $recentAssignments = Assignment::whereIn('classlist_id', $classlistIds)
            ->with('classlist')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'type' => 'assignment',
                    'title' => $assignment->title,
                    'classlist' => [
                        'id' => $assignment->classlist->id,
                        'name' => $assignment->classlist->name,
                    ],
                    'created_at' => $assignment->created_at->toIso8601String(),
                ];
            });

        $recentItems = $recentActivities->concat($recentAssignments)
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        // Calculate average grade
        $allScores = collect();
        $allScores = $allScores->concat($activityGrades->pluck('percentage'));
        $allScores = $allScores->concat($assignmentGrades->pluck('percentage'));
        $allScores = $allScores->concat($quizGrades->pluck('percentage'));
        $allScores = $allScores->concat($examGrades->pluck('percentage'));
        $averageGrade = $allScores->filter()->isNotEmpty() 
            ? round($allScores->filter()->average(), 2) 
            : null;

        return Inertia::render('Student/Dashboard', [
            'enrolledClasses' => $enrolledClasses,
            'upcomingDeadlines' => $upcomingDeadlines,
            'recentGrades' => $recentGrades,
            'pendingSubmissions' => $pendingSubmissions,
            'recentActivities' => $recentItems,
            'statistics' => [
                'totalClasses' => $enrolledClasses->count(),
                'totalItems' => $totalItems,
                'completedItems' => $completedItems,
                'completionPercentage' => $completionPercentage,
                'averageGrade' => $averageGrade,
                'upcomingDeadlinesCount' => $upcomingDeadlines->count(),
                'pendingSubmissionsCount' => $pendingSubmissions->count(),
            ],
        ]);
    }

    private function combineDateTime($date, $time)
    {
        if (!$date) {
            return null;
        }

        $dateTime = Carbon::parse($date);
        
        if ($time) {
            $timeParts = explode(':', $time);
            $dateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
        }

        return $dateTime;
    }
}
