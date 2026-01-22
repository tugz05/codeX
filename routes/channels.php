<?php

use App\Models\Classlist;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('classlist.{classlistId}.user.{userId}', function ($user, string $classlistId, int $userId) {
    if ((int) $user->id !== (int) $userId) {
        return false;
    }

    $classlist = Classlist::find($classlistId);
    if (! $classlist) {
        return false;
    }

    if ((int) $classlist->user_id === (int) $user->id) {
        return true;
    }

    return $classlist->students()
        ->wherePivot('status', 'active')
        ->where('users.id', $user->id)
        ->exists();
});
