<?php

namespace App\Http\Controllers;


use App\Models\Activity;
use App\Models\ActivityAttachment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classlist;
use App\Models\Criteria;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Classlist $classlist)
    {
        $criteria = Criteria::where('user_id', Auth::id())
    ->orderBy('created_at', 'desc')
    ->get(['id','title','description','grading_method']);
        $activities = Activity::with('attachments', 'criteria')
            ->where('classlist_id', $classlist->id)
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'instruction' => $a->instruction,
                'points' => $a->points,
                'due_date' => $a->due_date,
                'due_time' => $a->due_time,
                'accessible_date' => $a->accessible_date,
                'accessible_time' => $a->accessible_time,
                'created_at' => $a->created_at,
                'criteria_id' => optional($a->criteria->first())->id,
                'attachments' => $a->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                ]),
            ]);

        // Also get quizzes and examinations for the unified view
        $quizzes = \App\Models\Quiz::with('questions')
            ->where('classlist_id', $classlist->id)
            ->latest()
            ->get()
            ->map(fn($q) => [
                'id' => $q->id,
                'title' => $q->title,
                'description' => $q->description,
                'total_points' => $q->total_points,
                'time_limit' => $q->time_limit,
                'attempts_allowed' => $q->attempts_allowed,
                'is_published' => $q->is_published,
                'start_date' => $q->start_date,
                'end_date' => $q->end_date,
                'questions_count' => $q->questions->count(),
                'created_at' => $q->created_at,
            ]);

        $examinations = \App\Models\Examination::with('questions')
            ->where('classlist_id', $classlist->id)
            ->latest()
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'description' => $e->description,
                'total_points' => $e->total_points,
                'time_limit' => $e->time_limit,
                'attempts_allowed' => $e->attempts_allowed,
                'is_published' => $e->is_published,
                'require_proctoring' => $e->require_proctoring,
                'start_date' => $e->start_date,
                'end_date' => $e->end_date,
                'questions_count' => $e->questions->count(),
                'created_at' => $e->created_at,
            ]);

        // Also get assignments for the unified view
        $assignments = \App\Models\Assignment::with('attachments', 'author')
            ->where('classlist_id', $classlist->id)
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
                'created_at' => $a->created_at->toDateTimeString(),
                'attachments_count' => $a->attachments->count(),
            ]);

        // Also get materials for the unified view
        $materials = \App\Models\Material::with('attachments', 'author')
            ->where('classlist_id', $classlist->id)
            ->latest()
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => $m->description,
                'type' => $m->type,
                'link_url' => $m->link_url,
                'video_url' => $m->video_url,
                'accessible_date' => $m->accessible_date?->format('Y-m-d'),
                'accessible_time' => $m->accessible_time?->format('H:i'),
                'created_at' => $m->created_at->toDateTimeString(),
                'attachments_count' => $m->attachments->count(),
            ]);

        // Load students for the class
        $studentsModel = $classlist->students()
            ->where('classlist_user.status', 'active')
            ->withPivot('joined_at', 'status')
            ->orderBy('name')
            ->get();

        $students = $studentsModel->map(function ($student) {
                $joinedAt = $student->pivot->joined_at;
                // Handle both string and DateTime objects from pivot
                if ($joinedAt) {
                    if (is_string($joinedAt)) {
                        $joinedAt = \Carbon\Carbon::parse($joinedAt)->format('Y-m-d H:i:s');
                    } elseif ($joinedAt instanceof \DateTime || $joinedAt instanceof \Carbon\Carbon) {
                        $joinedAt = $joinedAt->format('Y-m-d H:i:s');
                    } else {
                        $joinedAt = null;
                    }
                } else {
                    $joinedAt = null;
                }

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'joined_at' => $joinedAt,
                    'status' => $student->pivot->status,
                ];
            })->values();

        $sessionModels = $classlist->attendanceSessions()
            ->with('records')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $attendanceSessions = $sessionModels->map(function ($session) {
            return [
                'id' => $session->id,
                'session_date' => $session->date?->format('Y-m-d'),
                'session_time' => null,
                'notes' => $session->notes,
                'total_students' => $session->records->count(),
                'present_count' => $session->records->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
                'absent_count' => $session->records->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
                'late_count' => $session->records->where('status', AttendanceRecord::STATUS_LATE)->count(),
                'excused_count' => $session->records->where('status', AttendanceRecord::STATUS_EXCUSED)->count(),
            ];
        });

        $attendanceStudentStats = $studentsModel->map(function ($student) use ($sessionModels) {
            $totalSessions = $sessionModels->count();
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $excusedCount = 0;

            foreach ($sessionModels as $session) {
                $record = $session->records->firstWhere('user_id', $student->id);

                if ($record) {
                    switch ($record->status) {
                        case AttendanceRecord::STATUS_PRESENT:
                            $presentCount++;
                            break;
                        case AttendanceRecord::STATUS_ABSENT:
                            $absentCount++;
                            break;
                        case AttendanceRecord::STATUS_LATE:
                            $lateCount++;
                            break;
                        case AttendanceRecord::STATUS_EXCUSED:
                            $excusedCount++;
                            break;
                    }
                } else {
                    $absentCount++;
                }
            }

            $attendancePercentage = $totalSessions > 0
                ? round((($presentCount + $excusedCount) / $totalSessions) * 100, 2)
                : 0;

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ],
                'total_sessions' => $totalSessions,
                'present' => $presentCount,
                'absent' => $absentCount,
                'late' => $lateCount,
                'excused' => $excusedCount,
                'attendance_percentage' => $attendancePercentage,
            ];
        })->values();

        return Inertia::render('Instructor/Activities/Index', [
            'classlist'  => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
                'section' => $classlist->section,
                'criteriaOptions' => $criteria,
            ],
            'other_classlists' => Classlist::where('user_id', Auth::id())
                ->where('id', '!=', $classlist->id)
                ->orderBy('name')
                ->get(['id', 'name', 'room', 'section', 'academic_year']),
            'activities' => $activities,
            'assignments' => $assignments,
            'quizzes' => $quizzes,
            'examinations' => $examinations,
            'materials' => $materials,
            'students' => $students,
            'total_students' => $students->count(),
            'attendance_sessions' => $attendanceSessions,
            'attendance_student_stats' => $attendanceStudentStats,
        ]);
    }


    public function create(Classlist $classlist)
    {
        return Inertia::render('Instructor/Activities/Create', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Classlist $classlist)
{
    // ...your authorization that this instructor owns the classlist...
    $auth = Auth::user()->id;
    $data = $request->validate([
        'title'            => ['required','string','max:200'],
        'instruction'      => ['nullable','string','max:10000'],
        'points'           => ['nullable','integer','min:0','max:100000'],
        'due_date'         => ['nullable','date'],
        'due_time'         => ['nullable','date_format:H:i'],
        'accessible_date'  => ['nullable','date'],
        'accessible_time'  => ['nullable','date_format:H:i'],
        'attachments.*'    => ['file','max:20480'], // 20MB each; adjust as needed
        'criteria_id'      => ['nullable','integer','exists:criterias,id'],
        'also_classlist_ids' => ['array'],
        'also_classlist_ids.*' => ['integer', 'exists:classlists,id'],
    ]);

    // If criteria_id is present, ensure it belongs to this instructor
    if (!empty($data['criteria_id'])) {
        $owns = \App\Models\Criteria::where('id', $data['criteria_id'])
            ->where('user_id', $auth)
            ->exists();
        abort_unless($owns, 403, 'You do not own the selected criteria.');
    }

    $targetIds = collect($data['also_classlist_ids'] ?? [])
        ->push($classlist->id)
        ->unique()
        ->values();

    $targetClasslists = Classlist::where('user_id', $auth)
        ->whereIn('id', $targetIds)
        ->get();

    abort_unless($targetClasslists->count() === $targetIds->count(), 403);

    $attachmentPayloads = [];
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('activity_attachments', 'public');
            $attachmentPayloads[] = [
                'name' => $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'url'  => Storage::disk('public')->url($path),
                'size' => $file->getSize(),
            ];
        }
    }

    $notificationService = app(NotificationService::class);

    foreach ($targetClasslists as $targetClasslist) {
        $activity = Activity::create([
            'classlist_id'     => $targetClasslist->id,
            'title'            => $data['title'],
            'posted_by'        => $auth,
            'instruction'      => $data['instruction'] ?? null,
            'points'           => $data['points'] ?? null,
            'due_date'         => $data['due_date'] ?? null,
            'due_time'         => $data['due_time'] ?? null,
            'accessible_date'  => $data['accessible_date'] ?? null,
            'accessible_time'  => $data['accessible_time'] ?? null,
        ]);

        foreach ($attachmentPayloads as $payload) {
            $activity->attachments()->create($payload);
        }

        if (!empty($data['criteria_id'])) {
            $activity->criteria()->sync([$data['criteria_id'] => ['assigned_points' => $data['points'] ?? null]]);
        }

        $students = $targetClasslist->students()->where('status', 'active')->get();
        foreach ($students as $student) {
            $actionUrl = route('student.activities.show', [$targetClasslist->id, $activity->id], false);
            $message = "A new activity '{$activity->title}' has been posted in {$targetClasslist->name}.";

            $notificationService->sendNotification(
                'activity_created',
                [$student],
                $activity->title,
                $message,
                Activity::class,
                $activity->id,
                $targetClasslist->id,
                $actionUrl
            );

            $notificationService->sendEmailNotification(
                'activity_created',
                $student,
                $activity->title,
                $message,
                url($actionUrl)
            );
        }
    }

    return back()->with('success', 'Activity created.');
}

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
// app/Http/Controllers/Instructor/ActivityController.php

public function update(Request $request, Classlist $classlist, Activity $activity)
{
    // authorize that this instructor owns the class
    $auth = Auth::user()->id;
    abort_unless($classlist->id === $activity->classlist_id, 404);

    $data = $request->validate([
        'title'            => ['required','string','max:200'],
        'instruction'      => ['nullable','string','max:10000'],
        'points'           => ['nullable','integer','min:0','max:100000'],
        'due_date'         => ['nullable','date'],
        'due_time'         => ['nullable','date_format:H:i'],
        'accessible_date'  => ['nullable','date'],
        'accessible_time'  => ['nullable','date_format:H:i'],
        'attachments.*'    => ['file','max:20480'],
        'attachments_remove'=> ['array'],
        'attachments_remove.*' => ['integer','exists:activity_attachments,id'],
        'criteria_id'      => ['nullable','integer','exists:criterias,id'],
    ]);

    // If criteria_id given, ensure it belongs to the instructor
    if (!empty($data['criteria_id'])) {
        $owns = Criteria::where('id', $data['criteria_id'])
            ->where('user_id', $auth)
            ->exists();
        abort_unless($owns, 403, 'You do not own the selected criteria.');
    }

    $activity->update([
        'classlist_id'     => $classlist->id,
        'title'            => $data['title'],
        'posted_by'        => $auth,
        'instruction'      => $data['instruction'] ?? null,
        'points'           => $data['points'] ?? null,
        'due_date'         => $data['due_date'] ?? null,
        'due_time'         => $data['due_time'] ?? null,
        'accessible_date'  => $data['accessible_date'] ?? null,
        'accessible_time'  => $data['accessible_time'] ?? null,
    ]);

    // Remove attachments
    if (!empty($data['attachments_remove'])) {
        $ids = $data['attachments_remove'];
        $toDelete = $activity->attachments()->whereIn('id', $ids)->get();
        foreach ($toDelete as $att) {
            // delete disk file if you want
            if ($att->url && str_starts_with($att->url, asset('storage'))) {
                // optional: Storage::disk('public')->delete(Str::after($att->url, asset('storage').'/'));
            }
            $att->delete();
        }
    }

    // Add new attachments
    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('activity_attachments', 'public');
            $activity->attachments()->create([
                'name' => $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'url'  => Storage::disk('public')->url($path),
                'size' => $file->getSize(),
            ]);
        }
    }

    // Sync criteria pivot (or detach if null)
    if (array_key_exists('criteria_id', $data)) {
        if ($data['criteria_id']) {
            $activity->criteria()->sync([$data['criteria_id'] => ['assigned_points' => $data['points'] ?? null]]);
        } else {
            $activity->criteria()->detach();
        }
    }

    return back()->with('success', 'Activity updated.');
}


    public function destroy(Classlist $classlist, Activity $activity)
    {
        // Ensure the activity belongs to the class in the URL
        if ($activity->classlist_id !== $classlist->id) {
            abort(404);
        }

        // (optional) Gate/Policy check
        // $this->authorize('delete', $activity);

        // Delete files from storage (optional but nice)
        foreach ($activity->attachments as $att) {
            if ($att->url && str_starts_with($att->url, 'storage/')) {
                $path = str_replace('storage/', '', $att->url);
                Storage::disk('public')->delete($path);
            }
        }

        // DB cascade from attachments -> activity is already configured,
        // but calling delete() on the activity will cascade via FK.
        $activity->delete();

        return back()->with('success', 'Activity deleted successfully.');
    }
}
