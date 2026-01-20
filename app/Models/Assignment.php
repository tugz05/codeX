<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'classlist_id',
        'title',
        'posted_by',
        'instruction',
        'points',
        'due_date',
        'due_time',
        'accessible_date',
        'accessible_time',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_time' => 'datetime:H:i',
        'accessible_date' => 'date',
        'accessible_time' => 'datetime:H:i',
    ];

    public function classlist()
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function attachments()
    {
        return $this->hasMany(AssignmentAttachment::class);
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
