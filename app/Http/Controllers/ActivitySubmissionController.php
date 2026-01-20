<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivitySubmissionController extends Controller
{
  protected function ensureEnrolled(Classlist $classlist) {
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403);
    }

    public function show(Request $request, Classlist $classlist, Activity $activity)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($activity->classlist_id === $classlist->id, 404);

        // Pull latest submission (draft or submitted) for convenience
        $latest = ActivitySubmission::where('activity_id', $activity->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->first();

        return Inertia::render('Student/Activities/Answer', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'activity' => [
                'id' => $activity->id,
                'title' => $activity->title,
                'instruction' => $activity->instruction,
                'points' => $activity->points,
                'due_date' => $activity->due_date,
                'due_time' => $activity->due_time,
            ],
            'submission' => $latest ? [
                'id' => $latest->id,
                'language' => $latest->language,
                'code' => $latest->code,
                'status' => $latest->status,
                'score' => $latest->score,
                'feedback' => $latest->feedback,
                'submitted_at' => $latest->submitted_at,
            ] : null,
        ]);
    }

    public function saveDraft(Request $request, Classlist $classlist, Activity $activity)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($activity->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'language' => ['nullable', 'string', 'max:32'],
            'code'     => ['nullable', 'string'],
        ]);

        $submission = ActivitySubmission::create([
            'classlist_id' => $classlist->id,
            'activity_id'  => $activity->id,
            'user_id'      => Auth::id(),
            'language'     => $data['language'] ?? null,
            'code'         => $data['code'] ?? null,
            'status'       => 'draft',
        ]);

        return back()->with('success', 'Draft saved.')->with('submission_id', $submission->id);
    }

    public function submit(Request $request, Classlist $classlist, Activity $activity)
    {
        $this->ensureEnrolled($classlist);
        abort_unless($activity->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'language' => ['required', 'string', 'max:32'],
            'code'     => ['required', 'string'],
        ]);

        $submission = ActivitySubmission::create([
            'classlist_id' => $classlist->id,
            'activity_id'  => $activity->id,
            'user_id'      => Auth::id(),
            'language'     => $data['language'],
            'code'         => $data['code'],
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        // TODO: dispatch job to run tests / call AI checker, then update score/feedback/status->graded.
        // e.g., dispatch(new GradeSubmission($submission));

        return redirect()
            ->route('student.activities.show', ['classlist' => $classlist->id, 'activity' => $activity->id])
            ->with('success', 'Activity submitted successfully.');

    }
}
