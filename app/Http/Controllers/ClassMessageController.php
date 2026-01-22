<?php

namespace App\Http\Controllers;

use App\Events\ClassMessageSent;
use App\Models\Classlist;
use App\Models\ClassMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassMessageController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(Request $request, Classlist $classlist)
    {
        $user = $request->user();

        if ($classlist->user_id === $user->id) {
            $students = $classlist->students()
                ->wherePivot('status', 'active')
                ->orderBy('users.name')
                ->get(['users.id', 'users.name', 'users.email', 'users.avatar']);

            $selectedStudentId = $request->integer('student_id') ?: $students->first()?->id;
            if ($selectedStudentId && ! $students->contains('id', $selectedStudentId)) {
                $selectedStudentId = $students->first()?->id;
            }
            $messages = $selectedStudentId
                ? $this->getConversation($classlist->id, $user->id, $selectedStudentId)
                : collect();
            if ($selectedStudentId) {
                $this->markConversationRead($classlist->id, $user->id, $selectedStudentId);
            }

            $unreadCounts = ClassMessage::query()
                ->where('classlist_id', $classlist->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->groupBy('sender_id')
                ->pluck(DB::raw('count(*)'), 'sender_id');

            return response()->json([
                'mode' => 'instructor',
                'students' => $students->map(function ($student) use ($unreadCounts) {
                    $student->unread_count = (int) ($unreadCounts[$student->id] ?? 0);
                    return $student;
                }),
                'selected_student_id' => $selectedStudentId,
                'messages' => $this->serializeMessages($messages),
            ]);
        }

        $isEnrolled = $classlist->students()
            ->wherePivot('status', 'active')
            ->where('users.id', $user->id)
            ->exists();

        abort_unless($isEnrolled, 403);

        $instructor = $classlist->user()->select('id', 'name', 'email', 'avatar')->first();
        $messages = $instructor
            ? $this->getConversation($classlist->id, $user->id, $instructor->id)
            : collect();
        $unreadCount = $instructor
            ? ClassMessage::query()
                ->where('classlist_id', $classlist->id)
                ->where('sender_id', $instructor->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count()
            : 0;
        if ($instructor) {
            $this->markConversationRead($classlist->id, $user->id, $instructor->id);
        }

        return response()->json([
            'mode' => 'student',
            'instructor' => $instructor,
            'messages' => $this->serializeMessages($messages),
            'unread_count' => $unreadCount,
        ]);
    }

    public function store(Request $request, Classlist $classlist)
    {
        $user = $request->user();

        $isEnrolled = $classlist->students()
            ->wherePivot('status', 'active')
            ->where('users.id', $user->id)
            ->exists();

        abort_unless($isEnrolled, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ClassMessage::create([
            'classlist_id' => $classlist->id,
            'sender_id' => $user->id,
            'recipient_id' => $classlist->user_id,
            'body' => $data['body'],
        ]);

        $this->sendMessageNotification($message, $classlist, $user, $classlist->user);
        broadcast(new ClassMessageSent($message));

        return response()->json([
            'message' => $this->serializeMessage($message->loadMissing(['sender:id,name,avatar', 'recipient:id,name,avatar'])),
        ], 201);
    }

    public function storeForStudent(Request $request, Classlist $classlist, User $student)
    {
        $user = $request->user();
        abort_unless($classlist->user_id === $user->id, 403);

        $isEnrolled = $classlist->students()
            ->wherePivot('status', 'active')
            ->where('users.id', $student->id)
            ->exists();

        abort_unless($isEnrolled, 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = ClassMessage::create([
            'classlist_id' => $classlist->id,
            'sender_id' => $user->id,
            'recipient_id' => $student->id,
            'body' => $data['body'],
        ]);

        $this->sendMessageNotification($message, $classlist, $user, $student);
        broadcast(new ClassMessageSent($message));

        return response()->json([
            'message' => $this->serializeMessage($message->loadMissing(['sender:id,name,avatar', 'recipient:id,name,avatar'])),
        ], 201);
    }

    private function getConversation(string $classlistId, int $userA, int $userB): Collection
    {
        return ClassMessage::query()
            ->where('classlist_id', $classlistId)
            ->where(function ($query) use ($userA, $userB) {
                $query->where(function ($inner) use ($userA, $userB) {
                    $inner->where('sender_id', $userA)->where('recipient_id', $userB);
                })->orWhere(function ($inner) use ($userA, $userB) {
                    $inner->where('sender_id', $userB)->where('recipient_id', $userA);
                });
            })
            ->orderBy('created_at')
            ->with(['sender:id,name,avatar', 'recipient:id,name,avatar'])
            ->get();
    }

    private function markConversationRead(string $classlistId, int $viewerId, int $otherId): void
    {
        ClassMessage::query()
            ->where('classlist_id', $classlistId)
            ->where('sender_id', $otherId)
            ->where('recipient_id', $viewerId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    private function sendMessageNotification(
        ClassMessage $message,
        Classlist $classlist,
        User $sender,
        User $recipient
    ): void {
        if ($sender->id === $recipient->id) {
            return;
        }

        $title = 'New message from ' . $sender->name;
        $preview = str($message->body)->limit(120)->toString();
        $routeName = $recipient->account_type === 'instructor'
            ? 'instructor.activities.index'
            : 'student.activities.index';

        $actionUrl = route($routeName, $classlist->id);

        $this->notificationService->sendNotification(
            'message_received',
            [$recipient],
            $title,
            $preview,
            ClassMessage::class,
            $message->id,
            $classlist->id,
            $actionUrl
        );
    }

    private function serializeMessages(Collection $messages): array
    {
        return $messages->map(fn (ClassMessage $message) => $this->serializeMessage($message))->all();
    }

    private function serializeMessage(ClassMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'created_at' => $message->created_at?->toIso8601String(),
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
                'avatar' => $message->sender?->avatar,
            ],
            'recipient' => [
                'id' => $message->recipient?->id,
                'name' => $message->recipient?->name,
                'avatar' => $message->recipient?->avatar,
            ],
        ];
    }
}
