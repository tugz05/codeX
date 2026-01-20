<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Assignment;
use App\Models\Classlist;
use App\Models\Examination;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarController extends Controller
{
    /**
     * Display calendar view for instructor
     */
    public function indexInstructor()
    {
        $instructorId = Auth::id();

        // Get all classes for this instructor
        $classlists = Classlist::where('user_id', $instructorId)
            ->select('id', 'name', 'academic_year')
            ->get();

        // Get all deadlines from all classes
        $events = collect();

        foreach ($classlists as $classlist) {
            // Activities
            $activities = Activity::where('classlist_id', $classlist->id)
                ->whereNotNull('due_date')
                ->get()
                ->map(function ($activity) use ($classlist) {
                    $dueDateTime = $activity->due_date->copy();
                    if ($activity->due_time) {
                        $timeStr = is_string($activity->due_time) ? $activity->due_time : $activity->due_time->format('H:i');
                        $timeParts = explode(':', $timeStr);
                        $dueDateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
                    } else {
                        $dueDateTime->setTime(23, 59, 59); // End of day if no time
                    }
                    return [
                        'id' => 'activity-' . $activity->id,
                        'title' => $activity->title,
                        'type' => 'activity',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $dueDateTime->toIso8601String(),
                        'end' => $dueDateTime->copy()->addHour()->toIso8601String(),
                        'points' => $activity->points,
                        'color' => '#22c55e', // green
                    ];
                });

            // Assignments
            $assignments = Assignment::where('classlist_id', $classlist->id)
                ->whereNotNull('due_date')
                ->get()
                ->map(function ($assignment) use ($classlist) {
                    $dueDateTime = $assignment->due_date->copy();
                    if ($assignment->due_time) {
                        $timeStr = is_string($assignment->due_time) ? $assignment->due_time : $assignment->due_time->format('H:i');
                        $timeParts = explode(':', $timeStr);
                        $dueDateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
                    } else {
                        $dueDateTime->setTime(23, 59, 59); // End of day if no time
                    }
                    return [
                        'id' => 'assignment-' . $assignment->id,
                        'title' => $assignment->title,
                        'type' => 'assignment',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $dueDateTime->toIso8601String(),
                        'end' => $dueDateTime->copy()->addHour()->toIso8601String(),
                        'points' => $assignment->points,
                        'color' => '#6366f1', // indigo
                    ];
                });

            // Quizzes
            $quizzes = Quiz::where('classlist_id', $classlist->id)
                ->where('is_published', true)
                ->whereNotNull('end_date')
                ->get()
                ->map(function ($quiz) use ($classlist) {
                    return [
                        'id' => 'quiz-' . $quiz->id,
                        'title' => $quiz->title,
                        'type' => 'quiz',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $quiz->start_date?->toIso8601String(),
                        'end' => $quiz->end_date->toIso8601String(),
                        'points' => $quiz->total_points,
                        'color' => '#3b82f6', // blue
                    ];
                });

            // Examinations
            $examinations = Examination::where('classlist_id', $classlist->id)
                ->where('is_published', true)
                ->whereNotNull('end_date')
                ->get()
                ->map(function ($exam) use ($classlist) {
                    return [
                        'id' => 'examination-' . $exam->id,
                        'title' => $exam->title,
                        'type' => 'examination',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $exam->start_date?->toIso8601String(),
                        'end' => $exam->end_date->toIso8601String(),
                        'points' => $exam->total_points,
                        'color' => '#a855f7', // purple
                    ];
                });

            $events = $events->concat($activities)->concat($assignments)->concat($quizzes)->concat($examinations);
        }

        return Inertia::render('Instructor/Calendar/Index', [
            'events' => $events->values(),
            'classlists' => $classlists,
        ]);
    }

    /**
     * Display calendar view for student
     */
    public function indexStudent()
    {
        $userId = Auth::id();

        // Get all enrolled classes
        $enrolledClasses = \App\Models\Student\ClassListUser::where('user_id', $userId)
            ->where('status', 'active')
            ->with('classlist:id,name,academic_year')
            ->get();

        $classlists = $enrolledClasses->map(fn($enrollment) => [
            'id' => $enrollment->classlist->id,
            'name' => $enrollment->classlist->name,
            'academic_year' => $enrollment->classlist->academic_year,
        ]);

        $events = collect();

        foreach ($enrolledClasses as $enrollment) {
            $classlist = $enrollment->classlist;

            // Activities
            $activities = Activity::where('classlist_id', $classlist->id)
                ->whereNotNull('due_date')
                ->get()
                ->map(function ($activity) use ($classlist) {
                    $dueDateTime = $activity->due_date;
                    if ($activity->due_time) {
                        $timeParts = explode(':', $activity->due_time);
                        $dueDateTime = $activity->due_date->copy()->setTime((int)$timeParts[0], (int)($timeParts[1] ?? 0));
                    }
                    return [
                        'id' => 'activity-' . $activity->id,
                        'title' => $activity->title,
                        'type' => 'activity',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $dueDateTime->toIso8601String(),
                        'end' => $dueDateTime->toIso8601String(),
                        'points' => $activity->points,
                        'color' => '#22c55e',
                    ];
                });

            // Assignments
            $assignments = Assignment::where('classlist_id', $classlist->id)
                ->whereNotNull('due_date')
                ->get()
                ->map(function ($assignment) use ($classlist) {
                    $dueDateTime = $assignment->due_date;
                    if ($assignment->due_time) {
                        $timeParts = explode(':', $assignment->due_time);
                        $dueDateTime = $assignment->due_date->copy()->setTime((int)$timeParts[0], (int)($timeParts[1] ?? 0));
                    }
                    return [
                        'id' => 'assignment-' . $assignment->id,
                        'title' => $assignment->title,
                        'type' => 'assignment',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $dueDateTime->toIso8601String(),
                        'end' => $dueDateTime->toIso8601String(),
                        'points' => $assignment->points,
                        'color' => '#6366f1',
                    ];
                });

            // Quizzes
            $quizzes = Quiz::where('classlist_id', $classlist->id)
                ->where('is_published', true)
                ->whereNotNull('end_date')
                ->get()
                ->map(function ($quiz) use ($classlist) {
                    return [
                        'id' => 'quiz-' . $quiz->id,
                        'title' => $quiz->title,
                        'type' => 'quiz',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $quiz->start_date?->toIso8601String(),
                        'end' => $quiz->end_date->toIso8601String(),
                        'points' => $quiz->total_points,
                        'color' => '#3b82f6',
                    ];
                });

            // Examinations
            $examinations = Examination::where('classlist_id', $classlist->id)
                ->where('is_published', true)
                ->whereNotNull('end_date')
                ->get()
                ->map(function ($exam) use ($classlist) {
                    return [
                        'id' => 'examination-' . $exam->id,
                        'title' => $exam->title,
                        'type' => 'examination',
                        'classlist_id' => $classlist->id,
                        'classlist_name' => $classlist->name,
                        'start' => $exam->start_date?->toIso8601String(),
                        'end' => $exam->end_date->toIso8601String(),
                        'points' => $exam->total_points,
                        'color' => '#a855f7',
                    ];
                });

            $events = $events->concat($activities)->concat($assignments)->concat($quizzes)->concat($examinations);
        }

        return Inertia::render('Student/Calendar/Index', [
            'events' => $events->values(),
            'classlists' => $classlists,
        ]);
    }

    /**
     * Export calendar as iCal format
     */
    public function exportIcal(Request $request)
    {
        $userId = Auth::id();
        $accountType = Auth::user()->account_type;

        // Get events based on filters
        $classlistIds = $request->get('classlist_ids', []);
        $types = $request->get('types', ['activity', 'assignment', 'quiz', 'examination']);

        $events = collect();

        if ($accountType === 'instructor') {
            $classlists = Classlist::where('user_id', $userId);
            if (!empty($classlistIds)) {
                $classlists->whereIn('id', $classlistIds);
            }
            $classlists = $classlists->get();
        } else {
            $enrolledClasses = \App\Models\Student\ClassListUser::where('user_id', $userId)
                ->where('status', 'active');
            if (!empty($classlistIds)) {
                $enrolledClasses->whereIn('classlist_id', $classlistIds);
            }
            $enrolledClasses = $enrolledClasses->with('classlist')->get();
            $classlists = $enrolledClasses->pluck('classlist');
        }

        foreach ($classlists as $classlist) {
            if (in_array('activity', $types)) {
                $activities = Activity::where('classlist_id', $classlist->id)
                    ->whereNotNull('due_date')
                    ->get();
                foreach ($activities as $activity) {
                    $dueDateTime = $activity->due_date->copy();
                    if ($activity->due_time) {
                        $timeStr = is_string($activity->due_time) ? $activity->due_time : $activity->due_time->format('H:i');
                        $timeParts = explode(':', $timeStr);
                        $dueDateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
                    } else {
                        $dueDateTime->setTime(23, 59, 59);
                    }
                    $events->push([
                        'title' => $activity->title . ' - ' . $classlist->name,
                        'description' => $activity->instruction ?? '',
                        'start' => $dueDateTime,
                        'end' => $dueDateTime->copy()->addHour(),
                        'type' => 'activity',
                    ]);
                }
            }

            if (in_array('assignment', $types)) {
                $assignments = Assignment::where('classlist_id', $classlist->id)
                    ->whereNotNull('due_date')
                    ->get();
                foreach ($assignments as $assignment) {
                    $dueDateTime = $assignment->due_date->copy();
                    if ($assignment->due_time) {
                        $timeStr = is_string($assignment->due_time) ? $assignment->due_time : $assignment->due_time->format('H:i');
                        $timeParts = explode(':', $timeStr);
                        $dueDateTime->setTime((int)($timeParts[0] ?? 0), (int)($timeParts[1] ?? 0));
                    } else {
                        $dueDateTime->setTime(23, 59, 59);
                    }
                    $events->push([
                        'title' => $assignment->title . ' - ' . $classlist->name,
                        'description' => $assignment->instruction ?? '',
                        'start' => $dueDateTime,
                        'end' => $dueDateTime->copy()->addHour(),
                        'type' => 'assignment',
                    ]);
                }
            }

            if (in_array('quiz', $types)) {
                $quizzes = Quiz::where('classlist_id', $classlist->id)
                    ->where('is_published', true)
                    ->whereNotNull('end_date')
                    ->get();
                foreach ($quizzes as $quiz) {
                    $events->push([
                        'title' => $quiz->title . ' - ' . $classlist->name,
                        'description' => $quiz->description ?? '',
                        'start' => $quiz->start_date ?? $quiz->end_date,
                        'end' => $quiz->end_date,
                        'type' => 'quiz',
                    ]);
                }
            }

            if (in_array('examination', $types)) {
                $examinations = Examination::where('classlist_id', $classlist->id)
                    ->where('is_published', true)
                    ->whereNotNull('end_date')
                    ->get();
                foreach ($examinations as $exam) {
                    $events->push([
                        'title' => $exam->title . ' - ' . $classlist->name,
                        'description' => $exam->description ?? '',
                        'start' => $exam->start_date ?? $exam->end_date,
                        'end' => $exam->end_date,
                        'type' => 'examination',
                    ]);
                }
            }
        }

        // Generate iCal content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//CodeX//Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";

        foreach ($events as $event) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= "UID:" . uniqid() . "@codex.app\r\n";
            $ical .= "DTSTAMP:" . now()->format('Ymd\THis\Z') . "\r\n";
            $ical .= "DTSTART:" . $event['start']->format('Ymd\THis\Z') . "\r\n";
            $ical .= "DTEND:" . $event['end']->format('Ymd\THis\Z') . "\r\n";
            $ical .= "SUMMARY:" . $this->escapeIcalText($event['title']) . "\r\n";
            $ical .= "DESCRIPTION:" . $this->escapeIcalText($event['description']) . "\r\n";
            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return response($ical, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="codex-calendar.ics"');
    }

    /**
     * Escape text for iCal format
     */
    private function escapeIcalText($text)
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        return $text;
    }
}
