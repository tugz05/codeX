<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivitySubmission;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StudentActivityController extends Controller
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

        // Only show activities that are already accessible (if access window is set)
        $nowDate = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $activities = Activity::with('attachments')
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
            ->get();

        // Pull this student's latest submission per activity
        $userId = Auth::id();
        $subs = ActivitySubmission::where('classlist_id', $classlist->id)
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')   // latest first
            ->get()
            ->unique('activity_id')         // keep only latest per activity
            ->keyBy('activity_id');

        $now = Carbon::now();

        $mapped = $activities->map(function (Activity $a) use ($subs, $now) {
            $dueAt = $this->combineDueAt($a->due_date, $a->due_time);
            /** @var ActivitySubmission|null $sub */
            $sub   = $subs->get($a->id);

            $status = $this->displayStatus($sub, $dueAt, $now);

            return [
                'id'               => $a->id,
                'title'            => $a->title,
                'instruction'      => $a->instruction,
                'points'           => $a->points,
                'due_date'         => $a->due_date,
                'due_time'         => $a->due_time,
                'accessible_date'  => $a->accessible_date,
                'accessible_time'  => $a->accessible_time,
                'created_at'       => $a->created_at->toIso8601String(),
                // NEW: submission info for badge
                'status'           => $status,
                'score'            => $sub?->score,
                'submitted_at'     => optional($sub?->submitted_at)?->toIso8601String(),
                'attachments'      => $a->attachments->map(fn ($att) => [
                    'id'   => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url'  => $att->url,
                    'size' => $att->size,
                ])->values(),
            ];
        })->values();

        // Get published quizzes (show all published, date filtering is handled client-side or in individual controllers)
        $now = now();
        $userId = Auth::id();
        
        $quizzes = \App\Models\Quiz::where('classlist_id', $classlist->id)
            ->where('is_published', true)
            ->latest()
            ->get()
            ->map(function ($quiz) use ($userId) {
                $userAttempts = $quiz->userAttempts($userId)->get();
                $latestAttempt = $userAttempts->sortByDesc('created_at')->first();
                $latestSubmittedAttempt = $userAttempts->where('status', 'submitted')->sortByDesc('submitted_at')->first();

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'total_points' => $quiz->total_points,
                    'time_limit' => $quiz->time_limit,
                    'attempts_allowed' => $quiz->attempts_allowed,
                    'attempts_count' => $userAttempts->count(),
                    'can_attempt' => $userAttempts->count() < $quiz->attempts_allowed,
                    'latest_score' => $latestAttempt?->score,
                    'latest_percentage' => $latestAttempt?->percentage,
                    'latest_status' => $latestAttempt?->status,
                    'latest_attempt_id' => $latestSubmittedAttempt?->id,
                    'start_date' => $quiz->start_date?->toIso8601String(),
                    'end_date' => $quiz->end_date?->toIso8601String(),
                    'created_at' => $quiz->created_at->toIso8601String(),
                ];
            })
            ->values();

        // Get published examinations
        $examinations = \App\Models\Examination::where('classlist_id', $classlist->id)
            ->where('is_published', true)
            ->latest()
            ->get()
            ->map(function ($examination) use ($userId) {
                $userAttempts = $examination->userAttempts($userId)->get();
                $latestAttempt = $userAttempts->sortByDesc('created_at')->first();
                $latestSubmittedAttempt = $userAttempts->where('status', 'submitted')->sortByDesc('submitted_at')->first();

                return [
                    'id' => $examination->id,
                    'title' => $examination->title,
                    'description' => $examination->description,
                    'total_points' => $examination->total_points,
                    'time_limit' => $examination->time_limit,
                    'attempts_allowed' => $examination->attempts_allowed,
                    'attempts_count' => $userAttempts->count(),
                    'can_attempt' => $userAttempts->count() < $examination->attempts_allowed,
                    'latest_score' => $latestAttempt?->score,
                    'latest_percentage' => $latestAttempt?->percentage,
                    'latest_status' => $latestAttempt?->status,
                    'latest_attempt_id' => $latestSubmittedAttempt?->id,
                    'require_proctoring' => $examination->require_proctoring,
                    'start_date' => $examination->start_date?->toIso8601String(),
                    'end_date' => $examination->end_date?->toIso8601String(),
                    'created_at' => $examination->created_at->toIso8601String(),
                ];
            })
            ->values();

        // Get accessible assignments
        $assignments = \App\Models\Assignment::with('attachments', 'author')
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

        // Get accessible materials
        $materials = \App\Models\Material::with('attachments', 'author')
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
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'type' => $m->type,
                'link_url' => $m->link_url,
                'video_url' => $m->video_url,
                'embed_code' => $m->embed_code,
                'accessible_date' => $m->accessible_date?->format('Y-m-d'),
                'accessible_time' => $m->accessible_time?->format('H:i'),
                'created_at' => $m->created_at->toIso8601String(),
                'attachments_count' => $m->attachments->count(),
                'author' => [
                    'id' => $m->author->id,
                    'name' => $m->author->name,
                ],
            ]);

        return Inertia::render('Student/Activities/Index', [
            'classlist'  => [
                'id'            => $classlist->id,
                'name'          => $classlist->name,
                'room'          => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'activities' => $mapped->values()->toArray(),
            'assignments' => $assignments->toArray(),
            'quizzes' => $quizzes->toArray(),
            'examinations' => $examinations->toArray(),
            'materials' => $materials->toArray(),
        ]);
    }

    public function show(Request $request, Classlist $classlist, Activity $activity)
    {
        // Ensure enrolled
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();
        abort_unless($enrolled, 403);

        // Ensure activity belongs to class
        abort_unless($activity->classlist_id === $classlist->id, 404);

        // Ensure accessible
        $nowDate = now()->toDateString();
        $nowTime = now()->format('H:i:s');
        $isAccessible =
            (is_null($activity->accessible_date) && is_null($activity->accessible_time)) ||
            ($activity->accessible_date < $nowDate) ||
            ($activity->accessible_date == $nowDate &&
                (is_null($activity->accessible_time) || $activity->accessible_time <= $nowTime));

        abort_unless($isAccessible, 403);

        $activity->load('attachments', 'comments.user', 'comments.replies.user');

        // Latest submission for this user & activity
        $userId = Auth::id();
        $sub = ActivitySubmission::where('classlist_id', $classlist->id)
            ->where('activity_id', $activity->id)
            ->where('user_id', $userId)
            ->orderByDesc('submitted_at')
            ->first();

        $dueAt  = $this->combineDueAt($activity->due_date, $activity->due_time);
        $status = $this->displayStatus($sub, $dueAt, now());

        return Inertia::render('Student/Activities/Show', [
            'classlist' => [
                'id'            => $classlist->id,
                'name'          => $classlist->name,
                'room'          => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'activity' => [
                'id'          => $activity->id,
                'title'       => $activity->title,
                'instruction' => $activity->instruction,
                'points'      => $activity->points,
                'due_date'    => $activity->due_date,
                'due_time'    => $activity->due_time,
                'created_at'  => $activity->created_at,
                'attachments' => $activity->attachments->map(fn ($att) => [
                    'id'   => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url'  => $att->url,
                    'size' => $att->size,
                ])->values(),
                // NEW: submission status for the header badge on Show page
                'status'       => $status,
                'score'        => $sub?->score,
                'submitted_at' => optional($sub?->submitted_at)?->toIso8601String(),
                'comments' => $activity->comments()
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
        ]);
    }

    // -----------------------------
    // Helpers
    // -----------------------------

    /** Combine due_date + due_time into a Carbon (or null) for comparisons. */
    private function combineDueAt(?string $date, ?string $time): ?Carbon
    {
        if (!$date) return null;
        $t = $time ?: '00:00:00';
        try {
            return Carbon::parse($date.' '.$t);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Compute student-facing status label from submission + dueAt.
     * Uses your ActivitySubmission statuses:
     *   - 'evaluated' => Graded
     *   - if submitted_at exists:
     *        late if > dueAt, else Submitted
     *   - if no submission and now > dueAt => Missing
     *   - else Not Submitted
     */
    private function displayStatus(?ActivitySubmission $sub, ?Carbon $dueAt, Carbon $now): string
    {
        if ($sub?->status === 'graded') {
            return 'Graded';
        }
        if ($sub?->status === 'draft') {
            return 'Draft';
        }

        if ($sub?->submitted_at) {
            if ($dueAt && $sub->submitted_at->gt($dueAt)) {
                return 'Submitted Late';
            }
            return 'Submitted';
        }

        if ($dueAt && $now->gt($dueAt)) {
            return 'Missing';
        }

        return 'Not Submitted';
    }
}
