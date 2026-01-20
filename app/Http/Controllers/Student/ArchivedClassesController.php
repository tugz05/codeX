<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\ClassListUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ArchivedClassesController extends Controller
{
    /**
     * Display all archived classes for the current student
     */
    public function index()
    {
        $archivedEnrollments = ClassListUser::with(['classlist.section'])
            ->where('user_id', Auth::id())
            ->where('status', 'archive')
            ->orderBy('updated_at', 'desc')
            ->get();

        return Inertia::render('Student/ArchivedClasses/Index', [
            'archivedClasses' => $archivedEnrollments->map(function ($enrollment) {
                $classlist = $enrollment->classlist;
                return [
                    'id' => $classlist->id,
                    'name' => $classlist->name,
                    'academic_year' => $classlist->academic_year,
                    'room' => $classlist->room,
                    'section' => $classlist->section,
                    'joined_at' => $enrollment->joined_at,
                    'archived_at' => $enrollment->updated_at,
                ];
            })
        ]);
    }

    /**
     * Restore an archived class
     */
    public function restore(Request $request, $id)
    {
        $enrollment = ClassListUser::where('classlist_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        abort_unless($enrollment->status === 'archive', 400, 'Only archived classes can be restored.');

        $enrollment->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Class restored successfully.');
    }
}
