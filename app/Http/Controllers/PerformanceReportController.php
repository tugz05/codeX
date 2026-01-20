<?php

namespace App\Http\Controllers;

use App\Models\ActivitySubmission;
use App\Models\Classlist;
use App\Models\PerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use League\Csv\Writer;

class PerformanceReportController extends Controller
{
    public function index()
    {
        $reports = PerformanceReport::where('instructor_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Instructor/Reports/Index', [
            'reports' => $reports
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'classlist_id' => ['nullable', 'exists:classlists,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'report_type' => ['required', 'in:class,student,activity']
        ]);

        $query = ActivitySubmission::query()
            ->whereHas('activity', function ($query) {
                $query->whereHas('classlist', function ($q) {
                    $q->where('instructor_id', Auth::id());
                });
            });

        // Apply filters
        if ($validated['classlist_id']) {
            $query->whereHas('activity', function ($q) use ($validated) {
                $q->where('classlist_id', $validated['classlist_id']);
            });
        }

        if ($validated['from_date']) {
            $query->where('submitted_at', '>=', $validated['from_date']);
        }

        if ($validated['to_date']) {
            $query->where('submitted_at', '<=', $validated['to_date']);
        }

        // Generate report based on type
        $report = match($validated['report_type']) {
            'class' => $this->generateClassReport($query),
            'student' => $this->generateStudentReport($query),
            'activity' => $this->generateActivityReport($query),
        };

        return response()->json(['report' => $report]);
    }

    protected function generateClassReport($query)
    {
        return $query->with('activity.classlist')
            ->get()
            ->groupBy('activity.classlist.name')
            ->map(function ($submissions) {
                return [
                    'total_submissions' => $submissions->count(),
                    'average_score' => $submissions->where('status', 'graded')
                        ->avg(fn($s) => ($s->score / $s->activity->points) * 100),
                    'completion_rate' => ($submissions->whereIn('status', ['submitted', 'graded'])->count() / $submissions->count()) * 100
                ];
            });
    }

    protected function generateStudentReport($query)
    {
        return $query->with('student')
            ->get()
            ->groupBy('student.name')
            ->map(function ($submissions) {
                return [
                    'total_submissions' => $submissions->count(),
                    'average_score' => $submissions->where('status', 'graded')
                        ->avg(fn($s) => ($s->score / $s->activity->points) * 100),
                    'on_time_submissions' => $submissions->filter(fn($s) =>
                        $s->submitted_at <= optional($s->activity)->due_date
                    )->count()
                ];
            });
    }

    protected function generateActivityReport($query)
    {
        return $query->with('activity')
            ->get()
            ->groupBy('activity.title')
            ->map(function ($submissions) {
                return [
                    'total_attempts' => $submissions->count(),
                    'average_score' => $submissions->where('status', 'graded')
                        ->avg(fn($s) => ($s->score / $s->activity->points) * 100),
                    'completion_rate' => ($submissions->whereIn('status', ['submitted', 'graded'])->count() / $submissions->count()) * 100
                ];
            });
    }

    public function download(PerformanceReport $report)
    {
        // Verify ownership
        abort_unless($report->instructor_id === Auth::id(), 403);

        return Storage::download($report->file_path, $report->name);
    }
}
