<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassListRequest;
use App\Models\Classlist;
use App\Models\User;
use App\Notifications\ClassInvitationNotification;
use App\Services\ClassListService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClasslistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allClasses = Classlist::with(['students'])
        ->where('user_id', auth()->id())
        ->withCount(['students' => function ($query) {
            $query->where('classlist_user.status', 'active');
        }])
        ->orderBy('created_at', 'desc')
        ->get();

    $activeClasses = $allClasses->where('is_archived', false)->map(function ($class) {
        return [
            'id' => $class->id,
            'name' => $class->name,
            'room' => $class->room,
            'academic_year' => $class->academic_year,
            'section' => $class->section,
            'students_count' => $class->students_count,
            'created_at' => $class->created_at,
        ];
    });

    $archivedClasses = $allClasses->where('is_archived', true)->map(function ($class) {
        return [
            'id' => $class->id,
            'name' => $class->name,
            'room' => $class->room,
            'academic_year' => $class->academic_year,
            'section' => $class->section,
            'students_count' => $class->students_count,
            'created_at' => $class->created_at,
        ];
    });

    return Inertia::render('Instructor/ClassList/Index', [
        'classlist' => $activeClasses->values(),
        'archivedClasses' => $archivedClasses->values(),
        'total_classes' => $activeClasses->count(),
        'total_students' => $activeClasses->sum('students_count'),
    ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClassListRequest $request, ClassListService $service): RedirectResponse
    {
        $service->create($request->validated());
        return redirect()->back()->with('success', 'Class successfully created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Classlist $classlist)
    {
        //
    }

    /**
     * Display students enrolled in a class.
     */
    public function students(Classlist $classlist)
    {
        // Ensure the instructor owns this class
        abort_unless($classlist->user_id === auth()->id(), 403);


        // Load students with their enrollment details
        $students = $classlist->students()
            ->wherePivot('status', 'active')
            ->withPivot('joined_at', 'status')
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'joined_at' => $student->pivot->joined_at?->format('Y-m-d H:i:s'),
                    'status' => $student->pivot->status,
                ];
            });

        return Inertia::render('Instructor/ClassList/Students', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'section' => $classlist->section,
                'room' => $classlist->room,
                'academic_year' => $classlist->academic_year,
            ],
            'students' => $students,
            'total_students' => $students->count(),
        ]);
    }

    /**
     * Remove a student from the class.
     */
    public function removeStudent(Classlist $classlist, \App\Models\User $student)
    {
        abort_unless($classlist->user_id === auth()->id(), 403);

        $classlist->students()->detach($student->id);

        return back()->with('success', 'Student removed from class.');
    }

    /**
     * Invite/add a student to the class by email.
     */
    public function inviteStudent(Request $request, Classlist $classlist)
    {
        abort_unless($classlist->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $student = User::where('email', $validated['email'])->first();

        if (!$student) {
            return back()->withErrors(['email' => 'No student account found with that email.']);
        }

        if ($student->account_type !== 'student') {
            return back()->withErrors(['email' => 'This email is not a student account.']);
        }

        $existing = $classlist->students()
            ->withPivot('status')
            ->where('users.id', $student->id)
            ->first();

        if ($existing && $existing->pivot->status === 'active') {
            return back()->with('success', 'Student is already enrolled in this class.');
        }

        if ($existing) {
            $classlist->students()->updateExistingPivot($student->id, [
                'status' => 'active',
                'joined_at' => now(),
            ]);
        } else {
            $classlist->students()->attach($student->id, [
                'status' => 'active',
                'joined_at' => now(),
            ]);
        }

        $notification = new ClassInvitationNotification(
            $classlist->name,
            $classlist->user->name ?? 'Instructor',
            $classlist->id,
            route('student.class.show', $classlist->id)
        );

        $notification->onConnection('database')->onQueue('notifications');
        $student->notify($notification);

        return back()->with('success', 'Student added and invitation email sent.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Classlist $classlist)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, Classlist $classlist)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'section' => 'required|string|max:255',
        'academic_year' => 'required|string|max:20',
        'room' => 'required|string|max:100',
    ]);

    $classlist->update($validated);

    return back()->with('success', 'Class updated.');
}

    /**
     * Archive a class
     */
    public function archive(Classlist $classlist)
    {
        abort_unless($classlist->user_id === auth()->id(), 403);
        
        $classlist->update(['is_archived' => true]);

        return back()->with('success', 'Class archived successfully.');
    }

    /**
     * Restore an archived class
     */
    public function restore(Classlist $classlist)
    {
        abort_unless($classlist->user_id === auth()->id(), 403);
        abort_unless($classlist->is_archived, 400, 'Only archived classes can be restored.');

        $classlist->update(['is_archived' => false]);

        return back()->with('success', 'Class restored successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Classlist $classlist)
    {
        abort_unless($classlist->user_id === auth()->id(), 403);
        
        $classlist->delete();

        return back()->with('success', 'Class deleted successfully.');
    }
}
