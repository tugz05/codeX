<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSectionController extends Controller
{
    /**
     * Display a listing of sections.
     */
    public function index(Request $request)
    {
        $query = Section::with('user');

        // Filter by instructor
        if ($request->has('instructor_id') && $request->instructor_id !== 'all') {
            $query->where('user_id', $request->instructor_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('day', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $sections = $query->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $instructors = User::where('account_type', 'instructor')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Admin/Sections/Index', [
            'sections' => $sections,
            'instructors' => $instructors,
            'filters' => [
                'instructor_id' => $request->instructor_id ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created section.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'schedule_from' => 'required|date_format:H:i',
            'schedule_to' => 'required|date_format:H:i|after:schedule_from',
            'day' => 'required|string|max:255',
        ]);

        // Verify user is an instructor
        $user = User::findOrFail($validated['user_id']);
        if ($user->account_type !== 'instructor') {
            return back()->withErrors(['user_id' => 'Selected user must be an instructor.']);
        }

        Section::create($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section created successfully.');
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'schedule_from' => 'required|date_format:H:i',
            'schedule_to' => 'required|date_format:H:i|after:schedule_from',
            'day' => 'required|string|max:255',
        ]);

        // Verify user is an instructor
        $user = User::findOrFail($validated['user_id']);
        if ($user->account_type !== 'instructor') {
            return back()->withErrors(['user_id' => 'Selected user must be an instructor.']);
        }

        $section->update($validated);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section updated successfully.');
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section)
    {
        // Check if section is being used
        if ($section->classlist()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete section that is being used by classes.']);
        }

        $section->delete();

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section deleted successfully.');
    }
}
