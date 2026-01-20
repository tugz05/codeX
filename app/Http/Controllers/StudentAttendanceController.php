<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentAttendanceController extends Controller
{
    /**
     * Display student's attendance for a class
     */
    public function index(Classlist $classlist)
    {
        // Ensure student is enrolled
        $enrollment = \App\Models\Student\ClassListUser::where('classlist_id', $classlist->id)
            ->where('user_id', Auth::id())
            ->where('status', 'active')
            ->firstOrFail();

        $sessions = $classlist->attendanceSessions()
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $records = AttendanceRecord::whereIn('attendance_session_id', $sessions->pluck('id'))
            ->where('user_id', Auth::id())
            ->get()
            ->keyBy('attendance_session_id');

        $attendanceData = [];
        $presentCount = 0;
        $absentCount = 0;
        $lateCount = 0;
        $excusedCount = 0;

        foreach ($sessions as $session) {
            $record = $records->get($session->id);
            $status = $record ? $record->status : AttendanceRecord::STATUS_ABSENT;

            $attendanceData[] = [
                'session_id' => $session->id,
                'session_date' => $session->date?->format('Y-m-d'),
                'session_time' => null,
                'status' => $status,
                'notes' => $record?->remarks,
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

        return Inertia::render('Student/Attendance/Index', [
            'classlist' => [
                'id' => $classlist->id,
                'name' => $classlist->name,
                'academic_year' => $classlist->academic_year,
                'room' => $classlist->room,
            ],
            'attendanceData' => $attendanceData,
            'stats' => [
                'total_sessions' => $totalSessions,
                'present' => $presentCount,
                'absent' => $absentCount,
                'late' => $lateCount,
                'excused' => $excusedCount,
                'attendance_percentage' => $attendancePercentage,
            ],
        ]);
    }
}
