<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentAttachment;
use App\Models\Classlist;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    public function create(Classlist $classlist)
    {
        return Inertia::render('Instructor/Assignments/Create', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'other_classlists' => Classlist::where('user_id', Auth::id())
                ->where('id', '!=', $classlist->id)
                ->orderBy('name')
                ->get(['id', 'name', 'room', 'section', 'academic_year']),
        ]);
    }

    public function store(Request $request, Classlist $classlist)
    {
        $auth = Auth::user()->id;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'instruction' => ['nullable', 'string', 'max:10000'],
            'points' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'accessible_date' => ['nullable', 'date'],
            'accessible_time' => ['nullable', 'date_format:H:i'],
            'attachments.*' => ['file', 'max:20480'],
            'also_classlist_ids' => ['array'],
            'also_classlist_ids.*' => ['integer', 'exists:classlists,id'],
        ]);

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
                $path = $file->store('assignment-attachments', 'public');
                $attachmentPayloads[] = [
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'url' => Storage::url($path),
                    'size' => $file->getSize(),
                ];
            }
        }

        $notificationService = app(NotificationService::class);

        foreach ($targetClasslists as $targetClasslist) {
            $assignment = Assignment::create([
                'classlist_id' => $targetClasslist->id,
                'title' => $data['title'],
                'posted_by' => $auth,
                'instruction' => $data['instruction'] ?? null,
                'points' => $data['points'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'due_time' => $data['due_time'] ?? null,
                'accessible_date' => $data['accessible_date'] ?? null,
                'accessible_time' => $data['accessible_time'] ?? null,
            ]);

            foreach ($attachmentPayloads as $payload) {
                AssignmentAttachment::create($payload + ['assignment_id' => $assignment->id]);
            }

            $students = $targetClasslist->students()->where('status', 'active')->get();
            foreach ($students as $student) {
                $actionUrl = route('student.assignments.show', [$targetClasslist->id, $assignment->id], false);
                $message = "A new assignment '{$assignment->title}' has been posted in {$targetClasslist->name}.";

                $notificationService->sendNotification(
                    'assignment_created',
                    [$student],
                    $assignment->title,
                    $message,
                    Assignment::class,
                    $assignment->id,
                    $targetClasslist->id,
                    $actionUrl
                );

                $notificationService->sendEmailNotification(
                    'assignment_created',
                    $student,
                    $assignment->title,
                    $message,
                    url($actionUrl)
                );
            }
        }

        return redirect()->route('instructor.activities.index', $classlist)
            ->with('success', 'Assignment created successfully.');
    }

    public function show(Classlist $classlist, Assignment $assignment)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        
        $assignment->load('attachments', 'author', 'comments.user', 'comments.replies.user');

        return Inertia::render('Instructor/Assignments/Show', [
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
        ]);
    }

    public function edit(Classlist $classlist, Assignment $assignment)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        
        $assignment->load('attachments');

        return Inertia::render('Instructor/Assignments/Edit', [
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
                'attachments' => $assignment->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                ]),
            ],
        ]);
    }

    public function update(Request $request, Classlist $classlist, Assignment $assignment)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'instruction' => ['nullable', 'string', 'max:10000'],
            'points' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'accessible_date' => ['nullable', 'date'],
            'accessible_time' => ['nullable', 'date_format:H:i'],
            'attachments.*' => ['file', 'max:20480'],
            'attachments_remove' => ['array'],
            'attachments_remove.*' => ['integer', 'exists:assignment_attachments,id'],
        ]);

        $assignment->update([
            'title' => $data['title'],
            'instruction' => $data['instruction'] ?? null,
            'points' => $data['points'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'due_time' => $data['due_time'] ?? null,
            'accessible_date' => $data['accessible_date'] ?? null,
            'accessible_time' => $data['accessible_time'] ?? null,
        ]);

        // Remove attachments
        if (!empty($data['attachments_remove'])) {
            $attachmentsToRemove = AssignmentAttachment::whereIn('id', $data['attachments_remove'])
                ->where('assignment_id', $assignment->id)
                ->get();
            
            foreach ($attachmentsToRemove as $att) {
                if (Storage::disk('public')->exists(str_replace('/storage/', '', $att->url))) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $att->url));
                }
                $att->delete();
            }
        }

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('assignment-attachments', 'public');
                AssignmentAttachment::create([
                    'assignment_id' => $assignment->id,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'url' => Storage::url($path),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('instructor.activities.index', $classlist)
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Classlist $classlist, Assignment $assignment)
    {
        abort_unless($assignment->classlist_id === $classlist->id, 404);
        
        // Delete attachments
        foreach ($assignment->attachments as $att) {
            if (Storage::disk('public')->exists(str_replace('/storage/', '', $att->url))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $att->url));
            }
        }
        
        $assignment->delete();

        return back()->with('success', 'Assignment deleted successfully.');
    }
}
