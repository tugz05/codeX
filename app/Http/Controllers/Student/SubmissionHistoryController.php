<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubmissionHistoryController extends Controller
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

    public function index(Request $request, Classlist $classlist)
    {
        $this->ensureEnrolled($classlist);

        $submissions = ActivitySubmission::with(['activity', 'latestAiEvaluation'])
            ->where('user_id', Auth::id())
            ->where('classlist_id', $classlist->id)
            ->latest()
            ->get()
            ->map(fn($sub) => [
                'id' => $sub->id,
                'activity' => [
                    'id' => $sub->activity->id,
                    'title' => $sub->activity->title,
                    'points' => $sub->activity->points,
                    'due_date' => $sub->activity->due_date,
                ],
                'submitted_at' => $sub->submitted_at,
                'status' => $sub->status,
                'score' => optional($sub->latestAiEvaluation)->score,
                'evaluation' => $sub->latestAiEvaluation ? [
                    'feedback' => $sub->latestAiEvaluation->feedback,
                    'criteria_breakdown' => $sub->latestAiEvaluation->criteria_breakdown,
                ] : null,
            ]);

        return Inertia::render('Student/Submissions/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'submissions' => $submissions,
        ]);
    }

    public function show(Request $request, Classlist $classlist, Activity $activity, ActivitySubmission $activitySubmission)
    {
        $this->ensureEnrolled($classlist);

        abort_unless($activitySubmission->user_id === Auth::id(), 403);
        abort_unless($activitySubmission->classlist_id === $classlist->id, 404);
        abort_unless($activitySubmission->activity_id === $activity->id, 404);

        $activitySubmission->load('latestAiEvaluation');

        return Inertia::render('Student/Submissions/Show', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'points' => $activity->points,
                'due_date' => $activity->due_date,
            ],
            'submission' => [
                'id' => $activitySubmission->id,
                'code' => $activitySubmission->code,
                'language' => $activitySubmission->language,
                'submitted_at' => $activitySubmission->submitted_at,
                'status' => $activitySubmission->status,
                'score' => optional($activitySubmission->latestAiEvaluation)->score,
                'evaluation' => $activitySubmission->latestAiEvaluation ? [
                    'feedback' => $activitySubmission->latestAiEvaluation->feedback,
                    'criteria_breakdown' => $activitySubmission->latestAiEvaluation->criteria_breakdown,
                ] : null,
            ],
        ]);
    }
}
