<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StudentAssignmentController extends Controller
{
    public function index(Request $request, Classlist $classlist)
    {
        // Ensure current user is enrolled in this class
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);

        // Only show assignments that are already accessible (if access window is set)
        $nowDate = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $assignments = Assignment::with('attachments', 'author')
            ->where('classlist_id', $classlist->id)
            ->where(function ($q) use ($nowDate, $nowTime) {
                $q->where(function ($q0) {
                    $q0->whereNull('accessible_date')
                       ->whereNull('accessible_time');
                })->orWhere(function ($q1) use ($nowDate, $nowTime) {
                    $q1->where('accessible_date', '<', $nowDate)
                       ->orWhere(function ($q2) use ($nowDate, $nowTime) {
                           $q2->where('accessible_date', '=', $nowDate)
                              ->where(function ($q3) use ($nowTime) {
                                  $q3->whereNull('accessible_time')
                                     ->orWhere('accessible_time', '<=', $nowTime);
                              });
                       });
                });
            })
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'instruction' => $a->instruction,
                'points' => $a->points,
                'due_date' => $a->due_date?->format('Y-m-d'),
                'due_time' => $a->due_time?->format('H:i'),
                'accessible_date' => $a->accessible_date?->format('Y-m-d'),
                'accessible_time' => $a->accessible_time?->format('H:i'),
                'created_at' => $a->created_at->toIso8601String(),
                'attachments_count' => $a->attachments->count(),
                'author' => [
                    'id' => $a->author->id,
                    'name' => $a->author->name,
                ],
            ]);

        return Inertia::render('Student/Assignments/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'assignments' => $assignments,
        ]);
    }

    public function show(Classlist $classlist, Assignment $assignment)
    {
        // Ensure current user is enrolled in this class
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);
        abort_unless($assignment->classlist_id === $classlist->id, 404);

        // Check if assignment is accessible
        if ($assignment->accessible_date) {
            $accessibleDate = $assignment->accessible_date;
            $accessibleDateTime = Carbon::parse($accessibleDate->format('Y-m-d'));

            if ($assignment->accessible_time) {
                $timeParts = explode(':', $assignment->accessible_time);
                $accessibleDateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
            }

            abort_unless(Carbon::now() >= $accessibleDateTime, 403, 'This assignment is not yet accessible.');
        }

        $assignment->load('attachments', 'author', 'classlist', 'comments.user', 'comments.replies.user');

        // Get latest submission for this user
        $latestSubmission = \App\Models\AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', Auth::id())
            ->with(['attachments' => function($q) {
                $q->where('is_current', true)
                  ->with(['versions' => function($q2) {
                      $q2->orderBy('version', 'desc');
                  }]);
            }])
            ->latest()
            ->first();

        // Get folders for this submission
        $folders = [];
        if ($latestSubmission) {
            $folders = \App\Models\FileFolder::where('folderable_type', 'App\\Models\\AssignmentSubmission')
                ->where('folderable_id', $latestSubmission->id)
                ->where('user_id', Auth::id())
                ->with('children')
                ->orderBy('order')
                ->get()
                ->map(fn($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'description' => $f->description,
                    'parent_id' => $f->parent_id,
                ]);
        }

        return Inertia::render('Student/Assignments/Show', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'instruction' => $assignment->instruction,
                'points' => $assignment->points,
                'due_date' => $assignment->due_date?->format('Y-m-d'),
                'due_time' => $assignment->due_time?->format('H:i'),
                'accessible_date' => $assignment->accessible_date?->format('Y-m-d'),
                'accessible_time' => $assignment->accessible_time?->format('H:i'),
                'created_at' => $assignment->created_at->toIso8601String(),
                'author' => [
                    'id' => $assignment->author->id,
                    'name' => $assignment->author->name,
                ],
                'attachments' => $assignment->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                ]),
                'comments' => $assignment->comments()
                    ->whereNull('parent_id')
                    ->visibleTo(Auth::user())
                    ->with(['user', 'replies' => function($q) {
                        $q->visibleTo(Auth::user())->with('user');
                    }])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(fn($c) => [
                        'id' => $c->id,
                        'content' => $c->content,
                        'visibility' => $c->visibility,
                        'created_at' => $c->created_at->toIso8601String(),
                        'user' => [
                            'id' => $c->user->id,
                            'name' => $c->user->name,
                        ],
                        'replies' => $c->replies->map(fn($r) => [
                            'id' => $r->id,
                            'content' => $r->content,
                            'visibility' => $r->visibility,
                            'created_at' => $r->created_at->toIso8601String(),
                            'user' => [
                                'id' => $r->user->id,
                                'name' => $r->user->name,
                            ],
                        ]),
                    ]),
            ],
            'submission' => $latestSubmission ? [
                'id' => $latestSubmission->id,
                'submission_type' => $latestSubmission->submission_type,
                'link_url' => $latestSubmission->link_url,
                'video_url' => $latestSubmission->video_url,
                'status' => $latestSubmission->status,
                'score' => $latestSubmission->score,
                'feedback' => $latestSubmission->feedback,
                'submitted_at' => $latestSubmission->submitted_at?->toIso8601String(),
                'attachments' => $latestSubmission->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                    'version' => $att->version,
                    'is_current' => $att->is_current,
                    'folder_id' => $att->folder_id,
                    'versions' => $att->versions->map(fn($v) => [
                        'id' => $v->id,
                        'version' => $v->version,
                        'name' => $v->name,
                        'url' => $v->url,
                        'type' => $v->type,
                        'size' => $v->size,
                        'version_notes' => $v->version_notes,
                        'is_current' => $v->is_current,
                        'created_at' => $v->created_at->toIso8601String(),
                    ]),
                ]),
            ] : null,
            'folders' => $folders,
        ]);
    }
}
