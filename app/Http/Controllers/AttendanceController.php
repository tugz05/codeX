<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Display attendance management page for a class
     */
    public function index(Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);

        $sessions = $classlist->attendanceSessions()
            ->with(['records.user'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_date' => $session->date?->format('Y-m-d'),
                    'session_time' => null,
                    'notes' => $session->notes,
                    'total_students' => $session->records->count(),
                    'present_count' => $session->records->where('status', AttendanceRecord::STATUS_PRESENT)->count(),
                    'absent_count' => $session->records->where('status', AttendanceRecord::STATUS_ABSENT)->count(),
                    'late_count' => $session->records->where('status', AttendanceRecord::STATUS_LATE)->count(),
                    'excused_count' => $session->records->where('status', AttendanceRecord::STATUS_EXCUSED)->count(),
                ];
            });

        $students = $classlist->students()
            ->where('classlist_user.status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ];
            });

        // Calculate attendance statistics per student
        $studentStats = [];
        foreach ($students as $student) {
            $totalSessions = $sessions->count();
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $excusedCount = 0;

            foreach ($sessions as $session) {
                $record = AttendanceRecord::where('attendance_session_id', $session['id'])
                    ->where('user_id', $student['id'])
                    ->first();

                if ($record) {
                    switch ($record->status) {
                        case AttendanceRecord::STATUS_PRESENT:
                            $presentCount++;
                            break;
                        case AttendanceRecord::STATUS_ABSENT:
                            $absentCount++;
                            break;
                        case AttendanceRecord::STATUS_LATE:
                            $lateCount++;
                            break;
                        case AttendanceRecord::STATUS_EXCUSED:
                            $excusedCount++;
                            break;
                    }
                } else {
                    $absentCount++; // Not marked = absent
                }
            }

            $attendancePercentage = $totalSessions > 0
                ? round((($presentCount + $excusedCount) / $totalSessions) * 100, 2)
                : 0;

            $studentStats[] = [
                'student' => $student,
                'total_sessions' => $totalSessions,
                'present' => $presentCount,
                'absent' => $absentCount,
                'late' => $lateCount,
                'excused' => $excusedCount,
                'attendance_percentage' => $attendancePercentage,
            ];
        }

        return Inertia::render('Instructor/Attendance/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'academic_year' => $classlist->academic_year,
                'room' => $classlist->room,
            ],
            'sessions' => $sessions,
            'students' => $students,
            'studentStats' => $studentStats,
        ]);
    }

    /**
     * Store a new attendance session
     */
    public function store(Request $request, Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'session_date' => 'required|date',
            'session_time' => 'nullable',
            'notes' => 'nullable|string|max:500',
            'records' => 'required|array',
            'records.*.user_id' => 'required|exists:users,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
            'records.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($classlist, $validated) {
            $session = AttendanceSession::create([
                'classlist_id' => $classlist->id,
                'date' => $validated['session_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['records'] as $recordData) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'user_id' => $recordData['user_id'],
                    'status' => $recordData['status'],
                    'remarks' => $recordData['notes'] ?? null,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Attendance marked successfully.');
    }

    /**
     * Show the form for editing an attendance session
     */
    public function edit(AttendanceSession $session)
    {
        abort_unless($session->classlist->user_id === Auth::id(), 403);

        $session->load(['records.user']);

        return response()->json([
            'id' => $session->id,
            'session_date' => $session->date?->format('Y-m-d'),
            'session_time' => null,
            'notes' => $session->notes,
            'records' => $session->records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'status' => $record->status,
                    'notes' => $record->notes,
                ];
            }),
        ]);
    }

    /**
     * Update an attendance session
     */
    public function update(Request $request, AttendanceSession $session)
    {
        abort_unless($session->classlist->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'session_date' => 'required|date',
            'session_time' => 'nullable',
            'notes' => 'nullable|string|max:500',
            'records' => 'required|array',
            'records.*.id' => 'nullable|exists:attendance_records,id',
            'records.*.user_id' => 'required|exists:users,id',
            'records.*.status' => 'required|in:present,absent,late,excused',
            'records.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($session, $validated) {
            $session->update([
                'date' => $validated['session_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete existing records
            $session->records()->delete();

            // Create new records
            foreach ($validated['records'] as $recordData) {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'user_id' => $recordData['user_id'],
                    'status' => $recordData['status'],
                    'remarks' => $recordData['notes'] ?? null,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Attendance updated successfully.');
    }

    /**
     * Delete an attendance session
     */
    public function destroy(AttendanceSession $session)
    {
        abort_unless($session->classlist->user_id === Auth::id(), 403);

        $session->delete();

        return redirect()->back()->with('success', 'Attendance session deleted successfully.');
    }

    /**
     * Get attendance report for a class
     */
    public function report(Classlist $classlist)
    {
        abort_unless($classlist->user_id === Auth::id(), 403);

        $sessions = $classlist->attendanceSessions()
            ->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $students = $classlist->students()
            ->where('classlist_user.status', 'active')
            ->orderBy('name')
            ->get();

        $reportData = [];
        foreach ($students as $student) {
            $records = [];
            $presentCount = 0;
            $absentCount = 0;
            $lateCount = 0;
            $excusedCount = 0;

            foreach ($sessions as $session) {
                $record = AttendanceRecord::where('attendance_session_id', $session->id)
                    ->where('user_id', $student->id)
                    ->first();

                $status = $record ? $record->status : AttendanceRecord::STATUS_ABSENT;
                $records[] = [
                    'session_id' => $session->id,
                    'session_date' => $session->date?->format('Y-m-d'),
                    'status' => $status,
                ];

                switch ($status) {
                    case AttendanceRecord::STATUS_PRESENT:
                        $presentCount++;
                        break;
                    case AttendanceRecord::STATUS_ABSENT:
                        $absentCount++;
                        break;
                    case AttendanceRecord::STATUS_LATE:
                        $lateCount++;
                        break;
                    case AttendanceRecord::STATUS_EXCUSED:
                        $excusedCount++;
                        break;
                }
            }

            $totalSessions = $sessions->count();
            $attendancePercentage = $totalSessions > 0
                ? round((($presentCount + $excusedCount) / $totalSessions) * 100, 2)
                : 0;

            $reportData[] = [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                ],
                'records' => $records,
                'stats' => [
                    'total_sessions' => $totalSessions,
                    'present' => $presentCount,
                    'absent' => $absentCount,
                    'late' => $lateCount,
                    'excused' => $excusedCount,
                    'attendance_percentage' => $attendancePercentage,
                ],
            ];
        }

        return Inertia::render('Instructor/Attendance/Report', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'academic_year' => $classlist->academic_year,
                'room' => $classlist->room,
            ],
            'sessions' => $sessions->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_date' => $session->session_date->format('Y-m-d'),
                    'session_time' => $session->session_time?->format('H:i'),
                ];
            }),
            'reportData' => $reportData,
        ]);
    }
}
