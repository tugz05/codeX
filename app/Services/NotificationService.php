<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Send a notification to users
     */
    public function sendNotification(
        string $typeKey,
        array $recipients,
        string $title,
        string $message,
        string $relatedType,
        int $relatedId,
        ?string $classlistId = null,
        ?string $actionUrl = null
    ): void {
        $data = [
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'type_key' => $typeKey,
        ];

        foreach ($recipients as $user) {
            if (!$user instanceof User) {
                $user = User::find($user);
                if (!$user) continue;
            }

            // Check user preferences
            $preferences = $this->getUserPreferences($user);
            $preferenceKey = $typeKey . '_in_app';
            
            if (!($preferences->$preferenceKey ?? true)) {
                continue; // User has disabled this notification type
            }

            // Create notification
            Notification::create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\' . $this->getNotificationClass($typeKey),
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => $data,
                'type_key' => $typeKey,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'classlist_id' => $classlistId,
            ]);
        }
    }

    /**
     * Send email notification
     */
    public function sendEmailNotification(
        string $typeKey,
        User $user,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): void {
        $preferences = $this->getUserPreferences($user);
        $preferenceKey = $typeKey . '_email';
        
        if (!($preferences->$preferenceKey ?? true)) {
            return; // User has disabled email notifications for this type
        }

        // Use Laravel's notification system for emails
        $notificationClass = 'App\\Notifications\\' . $this->getNotificationClass($typeKey);
        
        if (class_exists($notificationClass)) {
            $notification = new $notificationClass($title, $message, $actionUrl);
            $user->notify($notification);
        }
    }

    /**
     * Get user notification preferences
     */
    public function getUserPreferences(User $user): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'assignment_created_email' => true,
                'assignment_created_in_app' => true,
                'quiz_created_email' => true,
                'quiz_created_in_app' => true,
                'grade_released_email' => true,
                'grade_released_in_app' => true,
                'due_date_reminder_email' => true,
                'due_date_reminder_in_app' => true,
                'announcement_email' => true,
                'announcement_in_app' => true,
                'activity_created_email' => true,
                'activity_created_in_app' => true,
                'material_created_email' => true,
                'material_created_in_app' => true,
                'examination_created_email' => true,
                'examination_created_in_app' => true,
            ]
        );
    }

    /**
     * Get notification class name from type key
     */
    private function getNotificationClass(string $typeKey): string
    {
        return match($typeKey) {
            'assignment_created' => 'AssignmentCreatedNotification',
            'quiz_created' => 'QuizCreatedNotification',
            'activity_created' => 'ActivityCreatedNotification',
            'material_created' => 'MaterialCreatedNotification',
            'examination_created' => 'ExaminationCreatedNotification',
            'grade_released' => 'GradeReleasedNotification',
            'due_date_reminder' => 'DueDateReminderNotification',
            'announcement' => 'AnnouncementCreatedNotification',
            'message_received' => 'MessageReceivedNotification',
            default => 'AssignmentCreatedNotification',
        };
    }
}
