<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StudentMaterialController extends Controller
{
    /**
     * Display a listing of materials for a classlist.
     */
    public function index(Request $request, Classlist $classlist)
    {
        // Ensure current user is enrolled in this class
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);

        // Only show materials that are already accessible (if access window is set)
        $nowDate = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        $materials = Material::with('attachments', 'author')
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
                'url' => $m->url,
                'video_url' => $m->video_url,
                'embed_code' => $m->embed_code,
                'accessible_date' => $m->accessible_date,
                'accessible_time' => $m->accessible_time,
                'created_at' => $m->created_at,
                'author' => [
                    'id' => $m->author->id,
                    'name' => $m->author->name,
                ],
                'attachments' => $m->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->name,
                    'type' => $att->type,
                    'url' => $att->url,
                    'size' => $att->size,
                ]),
            ]);

        return Inertia::render('Student/Materials/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'materials' => $materials,
        ]);
    }

    /**
     * Display the specified material.
     */
    public function show(Classlist $classlist, Material $material)
    {
        // Ensure current user is enrolled in this class
        $enrolled = DB::table('classlist_user')
            ->where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->exists();

        abort_unless($enrolled, 403);
        abort_unless($material->classlist_id === $classlist->id, 404);

        // Check if material is accessible
        // Note: accessible_date is cast as 'date' in the model, so it's already a Carbon instance
        if ($material->accessible_date) {
            /** @var \Carbon\Carbon $accessibleDate */
            $accessibleDate = $material->accessible_date;
            $accessibleDateStr = $accessibleDate->format('Y-m-d');
            $accessibleDateTime = Carbon::parse($accessibleDateStr);

            if ($material->accessible_time) {
                $timeParts = explode(':', $material->accessible_time);
                $accessibleDateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
            }

            abort_unless(Carbon::now() >= $accessibleDateTime, 403, 'This material is not yet accessible.');
        }

        $material->load('attachments', 'author', 'classlist', 'comments.user', 'comments.replies.user');

        return Inertia::render('Student/Materials/Show', [
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
                'accessible_date' => $material->accessible_date?->format('Y-m-d'),
                'accessible_time' => $material->accessible_time?->format('H:i'),
                'created_at' => $material->created_at->toIso8601String(),
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
}
