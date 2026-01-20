<?php
namespace App\Http\Controllers;

use App\Models\Student\ClassListUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClasslistUserController extends Controller
{
    // List classes the current student joined
    public function index(Request $request)
    {
        // Handle unauthenticated users or authenticated non-students (for invite links with code parameter)
        if (!Auth::check() || (Auth::check() && Auth::user()->account_type !== 'student')) {
            return Inertia::render('Student/ClassList/Index', [
                'joinedClasses' => [],
                'archivedClasses' => [],
                'joinCode' => $request->query('code'),
                'requiresStudentAccount' => Auth::check() && Auth::user()->account_type !== 'student',
            ]);
        }

        $activeEnrollments = ClassListUser::with(['classlist'])
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->get();

        $archivedEnrollments = ClassListUser::with(['classlist'])
            ->where('user_id', Auth::id())
            ->where('status', 'archive')
            ->get();

        return Inertia::render('Student/ClassList/Index', [
            'joinedClasses' => $activeEnrollments->map(function ($enrollment) {
                $classlist = $enrollment->classlist;
                return [
                    'id' => $classlist->id,
                    'name' => $classlist->name,
                    'academic_year' => $classlist->academic_year,
                    'room' => $classlist->room,
                    'section' => $classlist->section,
                    'joined_at' => $enrollment->joined_at,
                ];
            }),
            'archivedClasses' => $archivedEnrollments->map(function ($enrollment) {
                $classlist = $enrollment->classlist;
                return [
                    'id' => $classlist->id,
                    'name' => $classlist->name,
                    'academic_year' => $classlist->academic_year,
                    'room' => $classlist->room,
                    'section' => $classlist->section,
                    'joined_at' => $enrollment->joined_at,
                ];
            }),
            'joinCode' => $request->query('code'),
            'requiresStudentAccount' => false,
        ]);
    }

    // Join a class by code
    public function join(Request $request)
    {
        // Require authentication to join
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to join the class.');
        }

        $request->validate([
            'class_code' => 'required|string|exists:classlists,id',
        ]);

        // Check if already joined
        $already = ClasslistUser::where('classlist_id', $request->class_code)
            ->where('user_id', Auth::id())
            ->first();

        if ($already && $already->status == 'active') {
            return back()->withErrors(['class_code' => 'You are already enrolled in this class.']);
        }

        // (Optional) Restore if previously unenrolled/archived
        if ($already) {
            $already->update([
                'status' => 'active',
                'joined_at' => now(),
            ]);
        } else {
            ClasslistUser::create([
                'classlist_id' => $request->class_code,
                'user_id' => Auth::id(),
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'You have joined the class!');
    }

    // Unenroll from a class
    public function unenroll(Request $request, $id)
    {
        $enrollment = ClasslistUser::where('classlist_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $enrollment->update(['status' => 'unenroll']);

        return redirect()->back()->with('success', 'You have left the class.');
    }

    // Archive a class
    public function archive(Request $request, $id)
    {
        $enrollment = ClasslistUser::where('classlist_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $enrollment->update(['status' => 'archive']);

        return redirect()->back()->with('success', 'Class archived successfully.');
    }

    // Restore an archived class
    public function restore(Request $request, $id)
    {
        $enrollment = ClasslistUser::where('classlist_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        abort_unless($enrollment->status === 'archive', 400, 'Only archived classes can be restored.');

        $enrollment->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Class restored successfully.');
    }

    // Get class details for student
    public function show(Request $request, $id)
    {
        // Ensure student is enrolled in this class
        $enrollment = ClassListUser::with(['classlist.user'])
            ->where('classlist_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $classlist = $enrollment->classlist;
        
        // Get students count
        $studentsCount = \App\Models\Student\ClassListUser::where('classlist_id', $id)
            ->where('status', 'active')
            ->count();

        // If it's an AJAX/API request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'id' => $classlist->id,
                'name' => $classlist->name,
                'section' => $classlist->section,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
                'instructor' => [
                    'name' => $classlist->user->name ?? 'Unknown',
                    'email' => $classlist->user->email ?? 'N/A',
                ],
                'students_count' => $studentsCount,
                'joined_at' => $enrollment->joined_at?->toISOString(),
                'created_at' => $classlist->created_at?->toISOString(),
            ]);
        }

        // For Inertia requests (shouldn't happen, but just in case)
        return Inertia::render('Student/ClassList/Index', [
            'classInfo' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'section' => $classlist->section,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
                'instructor' => [
                    'name' => $classlist->user->name ?? 'Unknown',
                    'email' => $classlist->user->email ?? 'N/A',
                ],
                'students_count' => $studentsCount,
                'joined_at' => $enrollment->joined_at?->toISOString(),
                'created_at' => $classlist->created_at?->toISOString(),
            ],
        ]);
    }
}
