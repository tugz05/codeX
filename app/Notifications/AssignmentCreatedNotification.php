<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionUrl = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Assignment: ' . $this->title)
            ->view('emails.notification', [
                'appName' => config('app.name', 'CodeX'),
                'title' => 'New Assignment',
                'message' => $this->message,
                'actionText' => 'View Assignment',
                'actionUrl' => $this->actionUrl ?? url('/'),
                'preheader' => $this->title,
            ]);
    }
}
