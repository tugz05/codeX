<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClassInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $className,
        public string $instructorName,
        public string $classCode,
        public ?string $actionUrl = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $body = "You have been added to {$this->className} by {$this->instructorName}.";
        $body .= " Class code: {$this->classCode}.";

        return (new MailMessage)
            ->subject('Class Invitation: ' . $this->className)
            ->view('emails.notification', [
                'appName' => config('app.name', 'CodeX'),
                'title' => 'Class Invitation',
                'body' => $body,
                'actionText' => 'Open Class',
                'actionUrl' => $this->actionUrl ?? url('/'),
                'preheader' => $this->className,
            ]);
    }
}
