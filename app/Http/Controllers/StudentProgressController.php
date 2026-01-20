<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\Classlist;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentProgressController extends Controller
{
    public function index()
    {
        $instructorId = Auth::id();

        $students = User::whereHas('classes', function ($query) use ($instructorId) {
            $query->where('instructor_id', $instructorId);
        })
        ->withCount(['submissions as total_submissions'])
        ->withCount(['submissions as graded_submissions' => function ($query) {
            $query->where('status', 'graded');
        }])
        ->withAvg(['submissions as average_score' => function ($query) {
            $query->where('status', 'graded')
                ->whereNotNull('score');
        }], DB::raw('(score / points) * 100'))
        ->get();

        return Inertia::render('Instructor/Progress/Index', [
            'students' => $students
        ]);
    }

    public function show(User $student)
    {
        $instructorId = Auth::id();

        // Verify this instructor has access to this student
        $hasAccess = $student->classes()->where('instructor_id', $instructorId)->exists();
        abort_unless($hasAccess, 403);

        // Get student's submission history
        $submissions = ActivitySubmission::with(['activity:id,title,points'])
            ->where('student_id', $student->id)
            ->whereHas('activity', function ($query) use ($instructorId) {
                $query->whereHas('classlist', function ($q) use ($instructorId) {
                    $q->where('instructor_id', $instructorId);
                });
            })
            ->latest()
            ->get();

        // Get performance metrics
        $metrics = [
            'totalSubmissions' => $submissions->count(),
            'gradedSubmissions' => $submissions->where('status', 'graded')->count(),
            'averageScore' => $submissions->where('status', 'graded')
                ->whereNotNull('score')
                ->avg(fn($s) => ($s->score / $s->activity->points) * 100) ?? 0,
            'onTimeSubmissions' => $submissions->filter(function ($s) {
                return $s->submitted_at <= optional($s->activity)->due_date;
            })->count(),
        ];

        return Inertia::render('Instructor/Progress/Show', [
            'student' => $student,
            'submissions' => $submissions,
            'metrics' => $metrics
        ]);
    }
}
