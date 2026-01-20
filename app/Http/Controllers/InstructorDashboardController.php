<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Classlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InstructorDashboardController extends Controller
{
    public function index()
    {
        $instructorId = Auth::id();

        // Get active classes count
        $activeClasses = Classlist::where('user_id', $instructorId)
            ->where('is_archived', false)
            ->count();

        // Get pending submissions count
        $pendingSubmissions = ActivitySubmission::whereHas('activity', function ($query) use ($instructorId) {
            $query->whereHas('classlist', function ($q) use ($instructorId) {
                $q->where('user_id', $instructorId);
            });
        })
        ->where('status', 'submitted')
        ->count();

        // Calculate average score
        $averageScore = ActivitySubmission::whereHas('activity', function ($query) use ($instructorId) {
            $query->whereHas('classlist', function ($q) use ($instructorId) {
                $q->where('user_id', $instructorId);
            });
        })
        ->where('status', 'graded')
        ->whereNotNull('score')
        ->avg(DB::raw('(score / points) * 100'));

        // Get total activities
        $totalActivities = Activity::whereHas('classlist', function ($query) use ($instructorId) {
            $query->where('user_id', $instructorId);
        })->count();

        return Inertia::render('Instructor/Dashboard/Index', [
            'stats' => [
                'activeClasses' => $activeClasses,
                'pendingSubmissions' => $pendingSubmissions,
                'averageScore' => round($averageScore ?? 0, 1),
                'totalActivities' => $totalActivities
            ]
        ]);
    }
}
