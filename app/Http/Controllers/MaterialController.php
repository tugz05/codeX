<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialAttachment;
use App\Models\Classlist;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MaterialController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Classlist $classlist)
    {
        return Inertia::render('Instructor/Materials/Create', [
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
        $auth = Auth::user()->id;
        
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['nullable', 'string', 'in:resource,link,document,video,other'],
            'url' => ['nullable', 'url', 'max:500', 'required_if:type,link'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'embed_code' => ['nullable', 'string', 'max:5000'],
            'accessible_date' => ['nullable', 'date'],
            'accessible_time' => ['nullable', 'date_format:H:i'],
            'attachments.*' => ['file', 'max:51200'], // 50MB each
        ], [
            'url.required_if' => 'URL is required when type is Link.',
        ]);

        // Additional validation: video type needs either video_url or embed_code
        if ($data['type'] === 'video' && empty($data['video_url']) && empty($data['embed_code'])) {
            return back()->withErrors(['video_url' => 'Either video URL or embed code is required for video type materials.']);
        }

        $material = Material::create([
            'classlist_id' => $classlist->id,
            'title' => $data['title'],
            'posted_by' => $auth,
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'resource',
            'url' => $data['url'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'embed_code' => $data['embed_code'] ?? null,
            'accessible_date' => $data['accessible_date'] ?? null,
            'accessible_time' => $data['accessible_time'] ?? null,
        ]);

        // Attach files
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('material_attachments', 'public');
                $material->attachments()->create([
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getClientMimeType(),
                    'url' => Storage::disk('public')->url($path),
                    'size' => $file->getSize(),
                ]);
            }
        }

        // Send notifications to enrolled students
        $notificationService = app(NotificationService::class);
        $students = $classlist->students()->where('status', 'active')->get();
        
        foreach ($students as $student) {
            $actionUrl = route('student.materials.show', [$classlist->id, $material->id], false);
            $message = "New material '{$material->title}' has been posted in {$classlist->name}.";
            
            // In-app notification
            $notificationService->sendNotification(
                'material_created',
                [$student],
                $material->title,
                $message,
                Material::class,
                $material->id,
                $classlist->id,
                $actionUrl
            );
            
            // Email notification
            $notificationService->sendEmailNotification(
                'material_created',
                $student,
                $material->title,
                $message,
                url($actionUrl)
            );
        }

        return redirect()->route('instructor.activities.index', $classlist)
            ->with('success', 'Material created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Classlist $classlist, Material $material)
    {
        $material->load('attachments', 'author', 'classlist', 'comments.user', 'comments.replies.user');

        return Inertia::render('Instructor/Materials/Show', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'material' => [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'type' => $material->type,
                'url' => $material->url,
                'video_url' => $material->video_url,
                'embed_code' => $material->embed_code,
                'accessible_date' => $material->accessible_date,
                'accessible_time' => $material->accessible_time,
                'created_at' => $material->created_at,
                'author' => [
                    'id' => $material->author->id,
                    'name' => $material->author->name,
                ],
                'attachments' => $material->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                ]),
                'comments' => $material->comments()
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classlist $classlist, Material $material)
    {
        abort_unless($material->classlist_id === $classlist->id, 404);

        $material->load('attachments');

        return Inertia::render('Instructor/Materials/Edit', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'material' => [
                'id' => $material->id,
                'title' => $material->title,
                'description' => $material->description,
                'type' => $material->type,
                'url' => $material->url,
                'video_url' => $material->video_url,
                'embed_code' => $material->embed_code,
                'accessible_date' => $material->accessible_date,
                'accessible_time' => $material->accessible_time,
                'attachments' => $material->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                ]),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Classlist $classlist, Material $material)
    {
        abort_unless($material->classlist_id === $classlist->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type' => ['nullable', 'string', 'in:resource,link,document,video,other'],
            'url' => ['nullable', 'url', 'max:500', 'required_if:type,link'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'embed_code' => ['nullable', 'string', 'max:5000'],
            'accessible_date' => ['nullable', 'date'],
            'accessible_time' => ['nullable', 'date_format:H:i'],
            'attachments.*' => ['file', 'max:20480'],
            'attachments_remove' => ['array'],
            'attachments_remove.*' => ['integer', 'exists:material_attachments,id'],
        ], [
            'url.required_if' => 'URL is required when type is Link.',
        ]);

        // Additional validation: video type needs either video_url or embed_code
        if ($data['type'] === 'video' && empty($data['video_url']) && empty($data['embed_code'])) {
            return back()->withErrors(['video_url' => 'Either video URL or embed code is required for video type materials.']);
        }

        $material->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'resource',
            'url' => $data['url'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'embed_code' => $data['embed_code'] ?? null,
            'accessible_date' => $data['accessible_date'] ?? null,
            'accessible_time' => $data['accessible_time'] ?? null,
        ]);

        // Remove attachments
        if (!empty($data['attachments_remove'])) {
            $ids = $data['attachments_remove'];
            $toDelete = $material->attachments()->whereIn('id', $ids)->get();
            foreach ($toDelete as $att) {
                if ($att->url && str_starts_with($att->url, asset('storage'))) {
                    $path = str_replace(asset('storage') . '/', '', $att->url);
                    Storage::disk('public')->delete($path);
                }
                $att->delete();
            }
        }

        // Add new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('material_attachments', 'public');
                $material->attachments()->create([
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getClientMimeType(),
                    'url' => Storage::disk('public')->url($path),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return redirect()->route('instructor.activities.index', $classlist)
            ->with('success', 'Material updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classlist $classlist, Material $material)
    {
        abort_unless($material->classlist_id === $classlist->id, 404);

        // Delete files from storage
        foreach ($material->attachments as $att) {
            if ($att->url && str_starts_with($att->url, asset('storage'))) {
                $path = str_replace(asset('storage') . '/', '', $att->url);
                Storage::disk('public')->delete($path);
            }
        }

        $material->delete();

        return back()->with('success', 'Material deleted successfully.');
    }
}
