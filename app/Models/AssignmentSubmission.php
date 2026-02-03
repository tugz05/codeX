<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssignmentSubmission extends Model
{
    use HasFactory;

    // Assignment Status Constants
    const STATUS_ASSIGNED = 'assigned';      // Work to be done
    const STATUS_TURNED_IN = 'turned_in';    // Submitted on time
    const STATUS_MISSING = 'missing';        // Past due, not submitted
    const STATUS_LATE = 'late';              // Submitted after deadline
    const STATUS_GRADED = 'graded';          // Graded/Returned by teacher

    protected $fillable = [
        'classlist_id',
        'assignment_id',
        'user_id',
        'submission_type',
        'link_url',
        'video_url',
        'status',
        'score',
        'feedback',
        'annotations',
        'criteria_id',
        'rubric_scores',
        'grade_override',
        'override_reason',
        'returned_to_student',
        'returned_at',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'returned_at' => 'datetime',
        'score' => 'integer',
        'annotations' => 'array',
        'rubric_scores' => 'array',
        'grade_override' => 'boolean',
        'returned_to_student' => 'boolean',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function classlist()
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(AssignmentSubmissionAttachment::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function criteria()
    {
        return $this->belongsTo(Criteria::class);
    }

    /**
     * Check if submission is late
     */
    public function isLate(): bool
    {
        return $this->status === self::STATUS_LATE;
    }

    /**
     * Check if submission is missing
     */
    public function isMissing(): bool
    {
        return $this->status === self::STATUS_MISSING;
    }

    /**
     * Check if submission is graded
     */
    public function isGraded(): bool
    {
        return $this->status === self::STATUS_GRADED;
    }

    /**
     * Check if submission is turned in (on time or late)
     */
    public function isTurnedIn(): bool
    {
        return in_array($this->status, [self::STATUS_TURNED_IN, self::STATUS_LATE]);
    }

    /**
     * Get status badge color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'blue',
            self::STATUS_TURNED_IN => 'green',
            self::STATUS_MISSING => 'red',
            self::STATUS_LATE => 'orange',
            self::STATUS_GRADED => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_TURNED_IN => 'Turned In',
            self::STATUS_MISSING => 'Missing',
            self::STATUS_LATE => 'Turned In: Done Late',
            self::STATUS_GRADED => 'Graded',
            default => 'Unknown',
        };
    }
}
