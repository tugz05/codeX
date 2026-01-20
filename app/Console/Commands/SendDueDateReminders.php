<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Assignment;
use App\Models\Quiz;
use App\Models\Examination;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDueDateReminders extends Command
{
    protected $signature = 'notifications:send-due-date-reminders';
    protected $description = 'Send due date reminder notifications to students';

    public function handle()
    {
        $notificationService = app(NotificationService::class);
        $now = Carbon::now();
        $reminderDays = [3, 1]; // 3 days before and 1 day before

        foreach ($reminderDays as $daysBefore) {
            $targetDate = $now->copy()->addDays($daysBefore)->startOfDay();
            $endDate = $targetDate->copy()->endOfDay();

            // Check assignments
            $assignments = Assignment::whereNotNull('due_date')
                ->whereBetween('due_date', [$targetDate, $endDate])
                ->with(['classlist.students' => function($q) {
                    $q->where('status', 'active');
                }])
                ->get();

            foreach ($assignments as $assignment) {
                foreach ($assignment->classlist->students as $student) {
                    // Check if already notified for this assignment
                    $alreadyNotified = \App\Models\Notification::where('notifiable_id', $student->id)
                        ->where('type_key', 'due_date_reminder')
                        ->where('related_type', Assignment::class)
                        ->where('related_id', $assignment->id)
                        ->whereDate('created_at', $now->toDateString())
                        ->exists();

                    if (!$alreadyNotified) {
                        $actionUrl = route('student.assignments.show', [$assignment->classlist_id, $assignment->id], false);
                        $message = "Reminder: Assignment '{$assignment->title}' is due in {$daysBefore} day(s) ({$assignment->due_date->format('M d, Y')}).";
                        
                        $notificationService->sendNotification(
                            'due_date_reminder',
                            [$student],
                            $assignment->title,
                            $message,
                            Assignment::class,
                            $assignment->id,
                            $assignment->classlist_id,
                            $actionUrl
                        );

                        $notificationService->sendEmailNotification(
                            'due_date_reminder',
                            $student,
                            $assignment->title,
                            $message,
                            url($actionUrl)
                        );
                    }
                }
            }

            // Check activities
            $activities = Activity::whereNotNull('due_date')
                ->whereBetween('due_date', [$targetDate, $endDate])
                ->with(['classlist.students' => function($q) {
                    $q->where('status', 'active');
                }])
                ->get();

            foreach ($activities as $activity) {
                foreach ($activity->classlist->students as $student) {
                    $alreadyNotified = \App\Models\Notification::where('notifiable_id', $student->id)
                        ->where('type_key', 'due_date_reminder')
                        ->where('related_type', Activity::class)
                        ->where('related_id', $activity->id)
                        ->whereDate('created_at', $now->toDateString())
                        ->exists();

                    if (!$alreadyNotified) {
                        $actionUrl = route('student.activities.show', [$activity->classlist_id, $activity->id], false);
                        $message = "Reminder: Activity '{$activity->title}' is due in {$daysBefore} day(s) ({$activity->due_date->format('M d, Y')}).";
                        
                        $notificationService->sendNotification(
                            'due_date_reminder',
                            [$student],
                            $activity->title,
                            $message,
                            Activity::class,
                            $activity->id,
                            $activity->classlist_id,
                            $actionUrl
                        );

                        $notificationService->sendEmailNotification(
                            'due_date_reminder',
                            $student,
                            $activity->title,
                            $message,
                            url($actionUrl)
                        );
                    }
                }
            }

            // Check quizzes
            $quizzes = Quiz::whereNotNull('end_date')
                ->whereBetween('end_date', [$targetDate, $endDate])
                ->where('is_published', true)
                ->with(['classlist.students' => function($q) {
                    $q->where('status', 'active');
                }])
                ->get();

            foreach ($quizzes as $quiz) {
                foreach ($quiz->classlist->students as $student) {
                    $alreadyNotified = \App\Models\Notification::where('notifiable_id', $student->id)
                        ->where('type_key', 'due_date_reminder')
                        ->where('related_type', Quiz::class)
                        ->where('related_id', $quiz->id)
                        ->whereDate('created_at', $now->toDateString())
                        ->exists();

                    if (!$alreadyNotified) {
                        $actionUrl = route('student.quizzes.show', [$quiz->classlist_id, $quiz->id], false);
                        $message = "Reminder: Quiz '{$quiz->title}' ends in {$daysBefore} day(s) ({$quiz->end_date->format('M d, Y')}).";
                        
                        $notificationService->sendNotification(
                            'due_date_reminder',
                            [$student],
                            $quiz->title,
                            $message,
                            Quiz::class,
                            $quiz->id,
                            $quiz->classlist_id,
                            $actionUrl
                        );

                        $notificationService->sendEmailNotification(
                            'due_date_reminder',
                            $student,
                            $quiz->title,
                            $message,
                            url($actionUrl)
                        );
                    }
                }
            }
        }

        $this->info('Due date reminders sent successfully.');
        return 0;
    }
}
