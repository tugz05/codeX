<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classlist;
use App\Models\ExamAttempt;
use App\Models\Examination;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GradebookController extends Controller
{
    /**
     * Display gradebook for a specific class
     */
    public function index(Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);

        // Get all students enrolled in this class
        $students = $classlist->students()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(fn($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ]);

        // Get all graded items
        $activities = Activity::where('classlist_id', $classlist->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($activity) => [
                'id' => $activity->id,
                'type' => 'activity',
                'title' => $activity->title,
                'points' => $activity->points ?? 0,
                'due_date' => $activity->due_date?->format('Y-m-d'),
            ]);

        $assignments = Assignment::where('classlist_id', $classlist->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($assignment) => [
                'id' => $assignment->id,
                'type' => 'assignment',
                'title' => $assignment->title,
                'points' => $assignment->points ?? 0,
                'due_date' => $assignment->due_date?->format('Y-m-d'),
            ]);

        $quizzes = Quiz::where('classlist_id', $classlist->id)
            ->where('is_published', true)
            ->orderBy('created_at')
            ->get()
            ->map(fn($quiz) => [
                'id' => $quiz->id,
                'type' => 'quiz',
                'title' => $quiz->title,
                'points' => $quiz->total_points ?? 0,
                'due_date' => $quiz->end_date?->format('Y-m-d'),
            ]);

        $examinations = Examination::where('classlist_id', $classlist->id)
            ->where('is_published', true)
            ->orderBy('created_at')
            ->get()
            ->map(fn($exam) => [
                'id' => $exam->id,
                'type' => 'examination',
                'title' => $exam->title,
                'points' => $exam->total_points ?? 0,
                'due_date' => $exam->end_date?->format('Y-m-d'),
            ]);

        $gradedItems = $activities->concat($assignments)->concat($quizzes)->concat($examinations);

        // Get grades for each student
        $studentGrades = [];
        foreach ($students as $student) {
            $grades = [];
            $totalPoints = 0;
            $earnedPoints = 0;

            // Activity grades
            foreach ($activities as $activity) {
                $submission = ActivitySubmission::where('activity_id', $activity['id'])
                    ->where('user_id', $student['id'])
                    ->whereNotNull('score')
                    ->where('status', 'graded')
                    ->latest('submitted_at')
                    ->first();

                if ($submission) {
                    $percentage = $activity['points'] > 0 
                        ? round(($submission->score / $activity['points']) * 100, 2) 
                        : 0;
                    $grades[] = [
                        'item_id' => $activity['id'],
                        'item_type' => 'activity',
                        'item_title' => $activity['title'],
                        'score' => $submission->score,
                        'points' => $activity['points'],
                        'percentage' => $percentage,
                        'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    ];
                    $totalPoints += $activity['points'];
                    $earnedPoints += $submission->score;
                } else {
                    $grades[] = [
                        'item_id' => $activity['id'],
                        'item_type' => 'activity',
                        'item_title' => $activity['title'],
                        'score' => null,
                        'points' => $activity['points'],
                        'percentage' => null,
                        'submitted_at' => null,
                    ];
                    $totalPoints += $activity['points'];
                }
            }

            // Assignment grades
            foreach ($assignments as $assignment) {
                $submission = AssignmentSubmission::where('assignment_id', $assignment['id'])
                    ->where('user_id', $student['id'])
                    ->whereNotNull('score')
                    ->where('status', 'graded')
                    ->latest('submitted_at')
                    ->first();

                if ($submission) {
                    $percentage = $assignment['points'] > 0 
                        ? round(($submission->score / $assignment['points']) * 100, 2) 
                        : 0;
                    $grades[] = [
                        'item_id' => $assignment['id'],
                        'item_type' => 'assignment',
                        'item_title' => $assignment['title'],
                        'score' => $submission->score,
                        'points' => $assignment['points'],
                        'percentage' => $percentage,
                        'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    ];
                    $totalPoints += $assignment['points'];
                    $earnedPoints += $submission->score;
                } else {
                    $grades[] = [
                        'item_id' => $assignment['id'],
                        'item_type' => 'assignment',
                        'item_title' => $assignment['title'],
                        'score' => null,
                        'points' => $assignment['points'],
                        'percentage' => null,
                        'submitted_at' => null,
                    ];
                    $totalPoints += $assignment['points'];
                }
            }

            // Quiz grades
            foreach ($quizzes as $quiz) {
                $attempt = QuizAttempt::where('quiz_id', $quiz['id'])
                    ->where('user_id', $student['id'])
                    ->whereNotNull('score')
                    ->where('status', 'submitted')
                    ->latest('submitted_at')
                    ->first();

                if ($attempt) {
                    $grades[] = [
                        'item_id' => $quiz['id'],
                        'item_type' => 'quiz',
                        'item_title' => $quiz['title'],
                        'score' => $attempt->score,
                        'points' => $attempt->total_points ?? $quiz['points'],
                        'percentage' => $attempt->percentage ?? ($attempt->total_points > 0 
                            ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                            : 0),
                        'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    ];
                    $totalPoints += ($attempt->total_points ?? $quiz['points']);
                    $earnedPoints += $attempt->score;
                } else {
                    $grades[] = [
                        'item_id' => $quiz['id'],
                        'item_type' => 'quiz',
                        'item_title' => $quiz['title'],
                        'score' => null,
                        'points' => $quiz['points'],
                        'percentage' => null,
                        'submitted_at' => null,
                    ];
                    $totalPoints += $quiz['points'];
                }
            }

            // Examination grades
            foreach ($examinations as $exam) {
                $attempt = ExamAttempt::where('examination_id', $exam['id'])
                    ->where('user_id', $student['id'])
                    ->whereNotNull('score')
                    ->where('status', 'submitted')
                    ->latest('submitted_at')
                    ->first();

                if ($attempt) {
                    $grades[] = [
                        'item_id' => $exam['id'],
                        'item_type' => 'examination',
                        'item_title' => $exam['title'],
                        'score' => $attempt->score,
                        'points' => $attempt->total_points ?? $exam['points'],
                        'percentage' => $attempt->percentage ?? ($attempt->total_points > 0 
                            ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                            : 0),
                        'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    ];
                    $totalPoints += ($attempt->total_points ?? $exam['points']);
                    $earnedPoints += $attempt->score;
                } else {
                    $grades[] = [
                        'item_id' => $exam['id'],
                        'item_type' => 'examination',
                        'item_title' => $exam['title'],
                        'score' => null,
                        'points' => $exam['points'],
                        'percentage' => null,
                        'submitted_at' => null,
                    ];
                    $totalPoints += $exam['points'];
                }
            }

            $overallPercentage = $totalPoints > 0 
                ? round(($earnedPoints / $totalPoints) * 100, 2) 
                : 0;

            // Calculate attendance percentage
            $totalSessions = AttendanceSession::where('classlist_id', $classlist->id)->count();
            $attendanceCount = 0;
            if ($totalSessions > 0) {
                $presentCount = AttendanceRecord::whereIn('attendance_session_id', function($query) use ($classlist) {
                    $query->select('id')
                        ->from('attendance_sessions')
                        ->where('classlist_id', $classlist->id);
                })
                ->where('user_id', $student['id'])
                ->whereIn('status', [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_EXCUSED])
                ->count();
                
                $attendanceCount = round(($presentCount / $totalSessions) * 100, 2);
            }

            $studentGrades[] = [
                'student' => $student,
                'grades' => $grades,
                'total_points' => $totalPoints,
                'earned_points' => $earnedPoints,
                'overall_percentage' => $overallPercentage,
                'attendance_percentage' => $attendanceCount,
            ];
        }

        // Calculate grade distribution
        $distribution = $this->calculateGradeDistribution($studentGrades);

        return Inertia::render('Instructor/Gradebook/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'academic_year' => $classlist->academic_year,
                'room' => $classlist->room,
            ],
            'graded_items' => $gradedItems,
            'student_grades' => $studentGrades,
            'distribution' => $distribution,
        ]);
    }

    /**
     * Calculate grade distribution
     */
    private function calculateGradeDistribution(array $studentGrades): array
    {
        $distribution = [
            'A' => 0,  // 90-100
            'B' => 0,  // 80-89
            'C' => 0,  // 70-79
            'D' => 0,  // 60-69
            'F' => 0,  // 0-59
        ];

        foreach ($studentGrades as $studentGrade) {
            $percentage = $studentGrade['overall_percentage'];
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

    /**
     * Export gradebook as Excel/CSV
     */
    public function exportExcel(Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);

        // Get the same data as index
        $students = $classlist->students()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $activities = Activity::where('classlist_id', $classlist->id)->orderBy('created_at')->get();
        $assignments = Assignment::where('classlist_id', $classlist->id)->orderBy('created_at')->get();
        $quizzes = Quiz::where('classlist_id', $classlist->id)->where('is_published', true)->orderBy('created_at')->get();
        $examinations = Examination::where('classlist_id', $classlist->id)->where('is_published', true)->orderBy('created_at')->get();

        $gradedItems = collect($activities)->concat($assignments)->concat($quizzes)->concat($examinations);

        // Generate CSV
        $filename = 'gradebook_' . $classlist->name . '_' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($students, $gradedItems, $activities, $assignments, $quizzes, $examinations, $classlist) {
            $file = fopen('php://output', 'w');
            
            // Header row
            $header = ['Student Name', 'Student Email'];
            foreach ($gradedItems as $item) {
                $header[] = $item->title . ' (' . ($item->points ?? $item->total_points ?? 0) . ' pts)';
            }
            $header[] = 'Total Points';
            $header[] = 'Earned Points';
            $header[] = 'Overall Percentage';
            $header[] = 'Attendance %';
            fputcsv($file, $header);

            // Data rows
            foreach ($students as $student) {
                $row = [$student->name, $student->email];
                $totalPoints = 0;
                $earnedPoints = 0;

                foreach ($activities as $activity) {
                    $submission = ActivitySubmission::where('activity_id', $activity->id)
                        ->where('user_id', $student->id)
                        ->whereNotNull('score')
                        ->where('status', 'graded')
                        ->latest('submitted_at')
                        ->first();
                    $row[] = $submission ? ($submission->score . '/' . $activity->points) : '-';
                    $totalPoints += $activity->points ?? 0;
                    $earnedPoints += $submission ? $submission->score : 0;
                }

                foreach ($assignments as $assignment) {
                    $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
                        ->where('user_id', $student->id)
                        ->whereNotNull('score')
                        ->where('status', 'graded')
                        ->latest('submitted_at')
                        ->first();
                    $row[] = $submission ? ($submission->score . '/' . $assignment->points) : '-';
                    $totalPoints += $assignment->points ?? 0;
                    $earnedPoints += $submission ? $submission->score : 0;
                }

                foreach ($quizzes as $quiz) {
                    $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                        ->where('user_id', $student->id)
                        ->whereNotNull('score')
                        ->where('status', 'submitted')
                        ->latest('submitted_at')
                        ->first();
                    $row[] = $attempt ? ($attempt->score . '/' . ($attempt->total_points ?? $quiz->total_points)) : '-';
                    $totalPoints += ($attempt ? ($attempt->total_points ?? $quiz->total_points) : ($quiz->total_points ?? 0));
                    $earnedPoints += $attempt ? $attempt->score : 0;
                }

                foreach ($examinations as $exam) {
                    $attempt = ExamAttempt::where('examination_id', $exam->id)
                        ->where('user_id', $student->id)
                        ->whereNotNull('score')
                        ->where('status', 'submitted')
                        ->latest('submitted_at')
                        ->first();
                    $row[] = $attempt ? ($attempt->score . '/' . ($attempt->total_points ?? $exam->total_points)) : '-';
                    $totalPoints += ($attempt ? ($attempt->total_points ?? $exam->total_points) : ($exam->total_points ?? 0));
                    $earnedPoints += $attempt ? $attempt->score : 0;
                }

                $overallPercentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
                
                // Calculate attendance percentage
                $totalSessions = AttendanceSession::where('classlist_id', $classlist->id)->count();
                $attendancePercentage = 0;
                if ($totalSessions > 0) {
                    $presentCount = AttendanceRecord::whereIn('attendance_session_id', function($query) use ($classlist) {
                        $query->select('id')
                            ->from('attendance_sessions')
                            ->where('classlist_id', $classlist->id);
                    })
                    ->where('user_id', $student->id)
                    ->whereIn('status', [AttendanceRecord::STATUS_PRESENT, AttendanceRecord::STATUS_EXCUSED])
                    ->count();
                    
                    $attendancePercentage = round(($presentCount / $totalSessions) * 100, 2);
                }
                
                $row[] = $totalPoints;
                $row[] = $earnedPoints;
                $row[] = $overallPercentage . '%';
                $row[] = $attendancePercentage . '%';
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export gradebook as PDF
     */
    public function exportPdf(Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);
        
        // For now, return a simple HTML view that can be printed as PDF
        // In production, you would use a package like DomPDF or Snappy
        $data = $this->index($classlist);
        
        // Return JSON with a message for now
        // TODO: Implement PDF export using DomPDF or similar package
        return response()->json([
            'message' => 'PDF export requires DomPDF package. Please install: composer require dompdf/dompdf',
            'data' => 'Use browser print functionality or install PDF package for server-side generation'
        ]);
    }
}
