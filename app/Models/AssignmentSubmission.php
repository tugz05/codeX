<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssignmentSubmission extends Model
{
    use HasFactory;

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
}
