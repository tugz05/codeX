<?php

namespace App\Http\Controllers;

use App\Models\Classlist;
use App\Models\GradeComponent;
use App\Models\GradeItem;
use App\Models\StudentGrade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClassRecordController extends Controller
{
    /**
     * Display the class record for a specific class
     */
    public function index($classlistId)
    {
        $classlist = Classlist::with([
            'students' => function ($query) {
                $query->where('classlist_user.status', 'active')
                    ->orderBy('last_name')
                    ->orderBy('first_name');
            },
            'gradeComponents' => function ($query) {
                $query->orderBy('order')
                    ->with(['gradeItems' => function ($q) {
                        $q->orderBy('order');
                    }]);
            }
        ])->findOrFail($classlistId);

        // Get all grades for all students
        $grades = StudentGrade::whereHas('gradeItem.gradeComponent', function ($query) use ($classlistId) {
            $query->where('classlist_id', $classlistId);
        })
        ->with(['gradeItem', 'student'])
        ->get()
        ->groupBy('user_id');

        // Calculate final grades for each student
        $studentFinalGrades = [];
        $gradeComponents = $classlist->gradeComponents ?? collect([]);
        
        foreach ($classlist->students as $student) {
            $finalGrade = $this->calculateFinalGrade($student->id, $gradeComponents);
            $studentFinalGrades[$student->id] = $finalGrade;
        }

        return Inertia::render('Instructor/ClassRecord/Index', [
            'classlist' => $classlist,
            'students' => $classlist->students ?? collect([]),
            'gradeComponents' => $gradeComponents,
            'grades' => $grades,
            'finalGrades' => $studentFinalGrades,
        ]);
    }

    /**
     * Store a new grade component
     */
    public function storeComponent(Request $request, $classlistId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'order' => 'integer|min:0',
        ]);

        $validated['classlist_id'] = $classlistId;

        $component = GradeComponent::create($validated);

        return back()->with('success', 'Grade component created successfully');
    }

    /**
     * Update a grade component
     */
    public function updateComponent(Request $request, $componentId)
    {
        $component = GradeComponent::findOrFail($componentId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'order' => 'integer|min:0',
        ]);

        $component->update($validated);

        return back()->with('success', 'Grade component updated successfully');
    }

    /**
     * Delete a grade component
     */
    public function destroyComponent($componentId)
    {
        $component = GradeComponent::findOrFail($componentId);
        $component->delete();

        return back()->with('success', 'Grade component deleted successfully');
    }

    /**
     * Store a new grade item
     */
    public function storeItem(Request $request, $componentId)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_points' => 'required|numeric|min:0',
            'date' => 'nullable|date',
            'description' => 'nullable|string',
            'order' => 'integer|min:0',
        ]);

        $validated['grade_component_id'] = $componentId;

        $item = GradeItem::create($validated);

        return back()->with('success', 'Grade item created successfully');
    }

    /**
     * Update a grade item
     */
    public function updateItem(Request $request, $itemId)
    {
        $item = GradeItem::findOrFail($itemId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_points' => 'required|numeric|min:0',
            'date' => 'nullable|date',
            'description' => 'nullable|string',
            'order' => 'integer|min:0',
        ]);

        $item->update($validated);

        return back()->with('success', 'Grade item updated successfully');
    }

    /**
     * Delete a grade item
     */
    public function destroyItem($itemId)
    {
        $item = GradeItem::findOrFail($itemId);
        $item->delete();

        return back()->with('success', 'Grade item deleted successfully');
    }

    /**
     * Update or create student grades (bulk update)
     */
    public function updateGrades(Request $request, $itemId)
    {
        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.user_id' => 'required|exists:users,id',
            'grades.*.points' => 'nullable|numeric|min:0',
            'grades.*.remarks' => 'nullable|string',
        ]);

        $item = GradeItem::findOrFail($itemId);

        DB::transaction(function () use ($validated, $item, $request) {
            foreach ($validated['grades'] as $gradeData) {
                if ($gradeData['points'] === null || $gradeData['points'] === '') {
                    // Delete grade if points are null/empty
                    StudentGrade::where('grade_item_id', $item->id)
                        ->where('user_id', $gradeData['user_id'])
                        ->delete();
                    continue;
                }

                StudentGrade::updateOrCreate(
                    [
                        'grade_item_id' => $item->id,
                        'user_id' => $gradeData['user_id'],
                    ],
                    [
                        'points' => $gradeData['points'],
                        'remarks' => $gradeData['remarks'] ?? null,
                        'graded_at' => Carbon::now('Asia/Manila'),
                        'graded_by' => $request->user()->id,
                    ]
                );
            }
        });

        return back()->with('success', 'Grades updated successfully');
    }

    /**
     * Calculate final grade for a student
     */
    private function calculateFinalGrade($userId, $gradeComponents)
    {
        $totalWeightedScore = 0;
        $totalWeight = 0;
        $componentBreakdown = [];

        // Handle null or empty grade components
        if (!$gradeComponents || $gradeComponents->isEmpty()) {
            return [
                'final_grade' => 0,
                'total_weighted_score' => 0,
                'breakdown' => [],
                'letter_grade' => 'N/A',
            ];
        }

        foreach ($gradeComponents as $component) {
            $totalMaxPoints = $component->gradeItems ? $component->gradeItems->sum('max_points') : 0;

            if ($totalMaxPoints == 0) {
                $componentBreakdown[] = [
                    'component' => $component->name ?? 'Unknown',
                    'percentage' => 0,
                    'weighted_score' => 0,
                    'weight' => $component->weight ?? 0,
                ];
                continue;
            }

            // Get student's total points for this component
            $studentPoints = StudentGrade::whereHas('gradeItem', function ($query) use ($component) {
                $query->where('grade_component_id', $component->id);
            })
            ->where('user_id', $userId)
            ->sum('points') ?? 0;

            // Calculate percentage for this component
            $componentPercentage = ($studentPoints / $totalMaxPoints) * 100;

            // Calculate weighted score
            $weightedScore = ($componentPercentage / 100) * ($component->weight ?? 0);

            $totalWeightedScore += $weightedScore;
            $totalWeight += ($component->weight ?? 0);

            $componentBreakdown[] = [
                'component' => $component->name ?? 'Unknown',
                'points' => $studentPoints,
                'max_points' => $totalMaxPoints,
                'percentage' => round($componentPercentage, 2),
                'weighted_score' => round($weightedScore, 2),
                'weight' => $component->weight ?? 0,
            ];
        }

        // Calculate final grade (out of 100)
        $finalGrade = $totalWeight > 0 ? ($totalWeightedScore / $totalWeight) * 100 : 0;

        return [
            'final_grade' => round($finalGrade, 2),
            'total_weighted_score' => round($totalWeightedScore, 2),
            'breakdown' => $componentBreakdown,
            'letter_grade' => $this->getLetterGrade($finalGrade),
        ];
    }

    /**
     * Get letter grade from numerical grade
     */
    private function getLetterGrade($grade)
    {
        if ($grade >= 97) return 'A+';
        if ($grade >= 93) return 'A';
        if ($grade >= 90) return 'A-';
        if ($grade >= 87) return 'B+';
        if ($grade >= 83) return 'B';
        if ($grade >= 80) return 'B-';
        if ($grade >= 77) return 'C+';
        if ($grade >= 73) return 'C';
        if ($grade >= 70) return 'C-';
        if ($grade >= 67) return 'D+';
        if ($grade >= 63) return 'D';
        if ($grade >= 60) return 'D-';
        return 'F';
    }

    /**
     * Export class record to CSV
     */
    public function export($classlistId)
    {
        $classlist = Classlist::with([
            'students' => function ($query) {
                $query->where('classlist_user.status', 'active')
                    ->orderBy('last_name')
                    ->orderBy('first_name');
            },
            'gradeComponents.gradeItems'
        ])->findOrFail($classlistId);

        $filename = slug($classlist->name) . '_class_record_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($classlist) {
            $file = fopen('php://output', 'w');

            // Header row
            $headerRow = ['Student ID', 'Last Name', 'First Name', 'Middle Name'];

            foreach ($classlist->gradeComponents as $component) {
                foreach ($component->gradeItems as $item) {
                    $headerRow[] = $component->name . ' - ' . $item->name . ' (' . $item->max_points . ')';
                }
                $headerRow[] = $component->name . ' Total';
                $headerRow[] = $component->name . ' %';
            }

            $headerRow[] = 'Final Grade';
            $headerRow[] = 'Letter Grade';

            fputcsv($file, $headerRow);

            // Data rows
            foreach ($classlist->students as $student) {
                $row = [
                    $student->student_id,
                    $student->last_name,
                    $student->first_name,
                    $student->middle_name,
                ];

                $finalGrade = $this->calculateFinalGrade($student->id, $classlist->gradeComponents);

                foreach ($classlist->gradeComponents as $component) {
                    foreach ($component->gradeItems as $item) {
                        $grade = StudentGrade::where('grade_item_id', $item->id)
                            ->where('user_id', $student->id)
                            ->first();

                        $row[] = $grade ? $grade->points : '';
                    }

                    // Component total and percentage
                    $componentData = collect($finalGrade['breakdown'])
                        ->firstWhere('component', $component->name);

                    $row[] = $componentData ? $componentData['points'] : 0;
                    $row[] = $componentData ? $componentData['percentage'] : 0;
                }

                $row[] = $finalGrade['final_grade'];
                $row[] = $finalGrade['letter_grade'];

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
