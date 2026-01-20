<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classlist;
use App\Models\Criteria;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AssignmentGradingController extends Controller
{
    /**
     * Display the grading interface for an assignment
     */
    public function index(Classlist $classlist, Assignment $assignment)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        abort_unless($classlist->user_id === Auth::id(), 403);

        // Get all submissions with student info
        $submissions = AssignmentSubmission::with(['user', 'attachments', 'criteria' => function($q) {
                $q->with('items');
            }])
            ->where('assignment_id', $assignment->id)
            ->get()
            ->map(function ($submission) use ($assignment) {
                return [
                    'id' => $submission->id,
                    'student' => [
                        'id' => $submission->user->id,
                        'name' => $submission->user->name,
                        'email' => $submission->user->email,
                    ],
                    'submission_type' => $submission->submission_type,
                    'link_url' => $submission->link_url,
                    'video_url' => $submission->video_url,
                    'attachments' => $submission->attachments->map(fn($att) => [
                        'id' => $att->id,
                        'name' => $att->name,
                        'url' => $att->url,
                        'size' => $att->size,
                    ]),
                    'status' => $submission->status,
                    'score' => $submission->score,
                    'feedback' => $submission->feedback,
                    'annotations' => $submission->annotations ?? [],
                    'criteria_id' => $submission->criteria_id,
                    'rubric_scores' => $submission->rubric_scores ?? [],
                    'rubric' => $submission->criteria ? [
                        'id' => $submission->criteria->id,
                        'title' => $submission->criteria->title,
                        'items' => $submission->criteria->items->map(fn($item) => [
                            'id' => $item->id,
                            'label' => $item->label,
                            'description' => $item->description,
                            'points' => $item->points,
                            'weight' => $item->weight,
                        ]),
                    ] : null,
                    'grade_override' => $submission->grade_override,
                    'override_reason' => $submission->override_reason,
                    'returned_to_student' => $submission->returned_to_student,
                    'returned_at' => $submission->returned_at?->toIso8601String(),
                    'submitted_at' => $submission->submitted_at?->toIso8601String(),
                    'created_at' => $submission->created_at->toIso8601String(),
                ];
            });

        // Get available rubrics for this instructor
        $rubrics = Criteria::where('user_id', Auth::id())
            ->with('items')
            ->get()
            ->map(function ($criteria) {
                return [
                    'id' => $criteria->id,
                    'title' => $criteria->title,
                    'description' => $criteria->description,
                    'grading_method' => $criteria->grading_method,
                    'items' => $criteria->items->map(fn($item) => [
                        'id' => $item->id,
                        'label' => $item->label,
                        'description' => $item->description,
                        'points' => $item->points,
                        'weight' => $item->weight,
                        'sort_order' => $item->sort_order,
                    ]),
                ];
            });

        return Inertia::render('Instructor/Assignments/Grading', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'academic_year' => $classlist->academic_year,
            ],
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'points' => $assignment->points,
                'due_date' => $assignment->due_date?->format('Y-m-d'),
                'due_time' => $assignment->due_time?->format('H:i'),
            ],
            'submissions' => $submissions,
            'rubrics' => $rubrics,
        ]);
    }

    /**
     * Grade a single submission
     */
    public function grade(Request $request, Classlist $classlist, Assignment $assignment, AssignmentSubmission $submission)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        abort_unless($submission->assignment_id === $assignment->id, 404);
        abort_unless($classlist->user_id === Auth::id(), 403);

        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:' . ($assignment->points ?? 999999)],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'annotations' => ['nullable', 'array'],
            'criteria_id' => ['nullable', 'exists:criteria,id'],
            'rubric_scores' => ['nullable', 'array'],
            'rubric_scores.*' => ['numeric', 'min:0'],
            'grade_override' => ['boolean'],
            'override_reason' => ['nullable', 'string', 'max:500'],
            'return_to_student' => ['boolean'],
        ]);

        DB::transaction(function () use ($submission, $data, $assignment, $classlist) {
            $submission->update([
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'annotations' => $data['annotations'] ?? [],
                'criteria_id' => $data['criteria_id'] ?? null,
                'rubric_scores' => $data['rubric_scores'] ?? [],
                'grade_override' => $data['grade_override'] ?? false,
                'override_reason' => $data['override_reason'] ?? null,
                'status' => 'graded',
            ]);

            if ($data['return_to_student'] ?? false) {
                $submission->update([
                    'returned_to_student' => true,
                    'returned_at' => now(),
                ]);

                // Send notification to student
                $notificationService = app(NotificationService::class);
                $student = $submission->user;
                $actionUrl = route('student.assignments.show', [$classlist->id, $assignment->id], false);
                $message = "Your submission for '{$assignment->title}' has been graded and returned with feedback.";

                $notificationService->sendNotification(
                    'grade_released',
                    [$student],
                    $assignment->title,
                    $message,
                    AssignmentSubmission::class,
                    $submission->id,
                    $classlist->id,
                    $actionUrl
                );
            }
        });

        return back()->with('success', 'Submission graded successfully.');
    }

    /**
     * Bulk grade multiple submissions
     */
    public function bulkGrade(Request $request, Classlist $classlist, Assignment $assignment)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        abort_unless($classlist->user_id === Auth::id(), 403);

        $data = $request->validate([
            'submissions' => ['required', 'array', 'min:1'],
            'submissions.*.id' => ['required', 'exists:assignment_submissions,id'],
            'submissions.*.score' => ['required', 'integer', 'min:0', 'max:' . ($assignment->points ?? 999999)],
            'submissions.*.feedback' => ['nullable', 'string', 'max:5000'],
            'return_to_students' => ['boolean'],
        ]);

        DB::transaction(function () use ($data, $assignment, $classlist) {
            $notificationService = app(NotificationService::class);
            $actionUrl = route('student.assignments.show', [$classlist->id, $assignment->id], false);

            foreach ($data['submissions'] as $submissionData) {
                $submission = AssignmentSubmission::findOrFail($submissionData['id']);
                
                $submission->update([
                    'score' => $submissionData['score'],
                    'feedback' => $submissionData['feedback'] ?? null,
                    'status' => 'graded',
                ]);

                if ($data['return_to_students'] ?? false) {
                    $submission->update([
                        'returned_to_student' => true,
                        'returned_at' => now(),
                    ]);

                    // Send notification
                    $student = $submission->user;
                    $message = "Your submission for '{$assignment->title}' has been graded and returned with feedback.";

                    $notificationService->sendNotification(
                        'grade_released',
                        [$student],
                        $assignment->title,
                        $message,
                        AssignmentSubmission::class,
                        $submission->id,
                        $classlist->id,
                        $actionUrl
                    );
                }
            }
        });

        return back()->with('success', count($data['submissions']) . ' submissions graded successfully.');
    }

    /**
     * Add annotation to a submission
     */
    public function addAnnotation(Request $request, Classlist $classlist, Assignment $assignment, AssignmentSubmission $submission)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        abort_unless($submission->assignment_id === $assignment->id, 404);
        abort_unless($classlist->user_id === Auth::id(), 403);

        $data = $request->validate([
            'annotation' => ['required', 'array'],
            'annotation.text' => ['required', 'string', 'max:1000'],
            'annotation.position' => ['nullable', 'string'], // e.g., "page:1,x:100,y:200" or "line:10"
            'annotation.type' => ['required', 'in:comment,highlight,note'],
        ]);

        $annotations = $submission->annotations ?? [];
        $annotations[] = array_merge($data['annotation'], [
            'id' => uniqid(),
            'created_at' => now()->toIso8601String(),
            'created_by' => Auth::id(),
        ]);

        $submission->update(['annotations' => $annotations]);

        return response()->json(['success' => true, 'annotations' => $annotations]);
    }

    /**
     * Remove annotation from a submission
     */
    public function removeAnnotation(Request $request, Classlist $classlist, Assignment $assignment, AssignmentSubmission $submission)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        abort_unless($submission->assignment_id === $assignment->id, 404);
        abort_unless($classlist->user_id === Auth::id(), 403);

        $data = $request->validate([
            'annotation_id' => ['required', 'string'],
        ]);

        $annotations = collect($submission->annotations ?? [])
            ->reject(fn($ann) => ($ann['id'] ?? null) === $data['annotation_id'])
            ->values()
            ->toArray();

        $submission->update(['annotations' => $annotations]);

        return response()->json(['success' => true, 'annotations' => $annotations]);
    }
}
