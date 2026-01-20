<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'assignment_created_email',
        'assignment_created_in_app',
        'quiz_created_email',
        'quiz_created_in_app',
        'activity_created_email',
        'activity_created_in_app',
        'material_created_email',
        'material_created_in_app',
        'examination_created_email',
        'examination_created_in_app',
        'grade_released_email',
        'grade_released_in_app',
        'due_date_reminder_email',
        'due_date_reminder_in_app',
        'announcement_email',
        'announcement_in_app',
    ];

    protected $casts = [
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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
