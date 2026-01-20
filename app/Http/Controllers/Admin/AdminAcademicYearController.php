<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminAcademicYearController extends Controller
{
    /**
     * Display a listing of academic years.
     */
    public function index(Request $request)
    {
        $query = AcademicYear::query();

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('semester', 'like', "%{$search}%")
                  ->orWhere('start_year', 'like', "%{$search}%")
                  ->orWhere('end_year', 'like', "%{$search}%");
            });
        }

        $academicYears = $query->orderBy('start_year', 'desc')
            ->orderBy('semester', 'asc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/AcademicYears/Index', [
            'academicYears' => $academicYears,
            'filters' => [
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester' => 'required|string|max:255',
            'start_year' => 'required|integer|min:2000|max:2100',
            'end_year' => 'required|integer|min:2000|max:2100|gte:start_year',
        ]);

        AcademicYear::create($validated);

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year created successfully.');
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'semester' => 'required|string|max:255',
            'start_year' => 'required|integer|min:2000|max:2100',
            'end_year' => 'required|integer|min:2000|max:2100|gte:start_year',
        ]);

        $academicYear->update($validated);

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year updated successfully.');
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(AcademicYear $academicYear)
    {
        // Check if academic year is being used
        if ($academicYear->classlist()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete academic year that is being used by classes.']);
        }

        $academicYear->delete();

        return redirect()->route('admin.academic-years.index')
            ->with('success', 'Academic year deleted successfully.');
    }
}
