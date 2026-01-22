<?php

namespace App\Events;

use App\Models\ClassMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClassMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ClassMessage $message)
    {
        $this->message->loadMissing(['sender:id,name,avatar', 'recipient:id,name,avatar', 'classlist:id,user_id']);
    }

    public function broadcastOn(): array
    {
        $classlistId = $this->message->classlist_id;

        return [
            new PrivateChannel("classlist.{$classlistId}.user.{$this->message->sender_id}"),
            new PrivateChannel("classlist.{$classlistId}.user.{$this->message->recipient_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'class-message.sent';
    }

    public function broadcastWith(): array
    {
        $classlist = $this->message->classlist;
        $instructorId = $classlist?->user_id;
        $studentId = $instructorId
            ? ($this->message->sender_id === $instructorId ? $this->message->recipient_id : $this->message->sender_id)
            : null;

        return [
            'classlist_id' => $this->message->classlist_id,
            'instructor_id' => $instructorId,
            'student_id' => $studentId,
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'sender_id' => $this->message->sender_id,
                'recipient_id' => $this->message->recipient_id,
                'created_at' => $this->message->created_at?->toIso8601String(),
                'sender' => [
                    'id' => $this->message->sender?->id,
                    'name' => $this->message->sender?->name,
                    'avatar' => $this->message->sender?->avatar,
                ],
                'recipient' => [
                    'id' => $this->message->recipient?->id,
                    'name' => $this->message->recipient?->name,
                    'avatar' => $this->message->recipient?->avatar,
                ],
            ],
        ];
    }
}
