<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 10);
        
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'notifications' => $notifications->map(fn($n) => [
                'id' => $n->id,
                'type_key' => $n->type_key,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'action_url' => $n->data['action_url'] ?? null,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        abort_unless($notification->notifiable_id === Auth::id(), 403);
        
        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete notification
     */
    public function destroy(Notification $notification)
    {
        abort_unless($notification->notifiable_id === Auth::id(), 403);
        
        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get notification preferences
     */
    public function preferences()
    {
        $user = Auth::user();
        $preferences = $this->notificationService->getUserPreferences($user);

        return Inertia::render('Notifications/Preferences', [
            'preferences' => [
                'assignment_created_email' => $preferences->assignment_created_email,
                'assignment_created_in_app' => $preferences->assignment_created_in_app,
                'quiz_created_email' => $preferences->quiz_created_email,
                'quiz_created_in_app' => $preferences->quiz_created_in_app,
                'activity_created_email' => $preferences->activity_created_email ?? true,
                'activity_created_in_app' => $preferences->activity_created_in_app ?? true,
                'material_created_email' => $preferences->material_created_email ?? true,
                'material_created_in_app' => $preferences->material_created_in_app ?? true,
                'examination_created_email' => $preferences->examination_created_email ?? true,
                'examination_created_in_app' => $preferences->examination_created_in_app ?? true,
                'grade_released_email' => $preferences->grade_released_email,
                'grade_released_in_app' => $preferences->grade_released_in_app,
                'due_date_reminder_email' => $preferences->due_date_reminder_email,
                'due_date_reminder_in_app' => $preferences->due_date_reminder_in_app,
                'announcement_email' => $preferences->announcement_email,
                'announcement_in_app' => $preferences->announcement_in_app,
            ],
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();
        $preferences = $this->notificationService->getUserPreferences($user);

        $validated = $request->validate([
            'assignment_created_email' => 'boolean',
            'assignment_created_in_app' => 'boolean',
            'quiz_created_email' => 'boolean',
            'quiz_created_in_app' => 'boolean',
            'activity_created_email' => 'boolean',
            'activity_created_in_app' => 'boolean',
            'material_created_email' => 'boolean',
            'material_created_in_app' => 'boolean',
            'examination_created_email' => 'boolean',
            'examination_created_in_app' => 'boolean',
            'grade_released_email' => 'boolean',
            'grade_released_in_app' => 'boolean',
            'due_date_reminder_email' => 'boolean',
            'due_date_reminder_in_app' => 'boolean',
            'announcement_email' => 'boolean',
            'announcement_in_app' => 'boolean',
        ]);

        $preferences->update($validated);

        return back()->with('success', 'Notification preferences updated successfully.');
    }
}
