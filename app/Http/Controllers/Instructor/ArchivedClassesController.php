<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ArchivedClassesController extends Controller
{
    /**
     * Display all archived classes for the current instructor
     */
    public function index()
    {
        $archivedClasses = Classlist::with(['section', 'students'])
            ->where('user_id', Auth::id())
            ->where('is_archived', true)
            ->withCount(['students' => function ($query) {
                $query->where('classlist_user.status', 'active');
            }])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->name,
                    'room' => $class->room,
                    'academic_year' => $class->academic_year,
                    'section' => $class->section,
                    'students_count' => $class->students_count,
                    'created_at' => $class->created_at,
                    'archived_at' => $class->updated_at,
                ];
            });

        return Inertia::render('Instructor/ArchivedClasses/Index', [
            'archivedClasses' => $archivedClasses,
            'total_classes' => $archivedClasses->count(),
            'total_students' => $archivedClasses->sum('students_count'),
        ]);
    }

    /**
     * Restore an archived class
     */
    public function restore(Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);
        abort_unless($classlist->is_archived, 400, 'Only archived classes can be restored.');

        $classlist->update(['is_archived' => false]);

        return redirect()->back()->with('success', 'Class restored successfully.');
    }
}
