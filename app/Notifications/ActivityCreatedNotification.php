<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Activity: ' . $this->title)
            ->view('emails.notification', [
                'appName' => config('app.name', 'CodeX'),
                'title' => 'New Activity',
                'message' => $this->message,
                'actionText' => 'View Activity',
                'actionUrl' => $this->actionUrl ?? url('/'),
                'preheader' => $this->title,
            ]);
    }
}
