<?php
namespace App\Models;

use App\Models\Classlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'classlist_id',
        'title',
        'posted_by',
        'instruction',
        'points',
        'due_date',
        'due_time',
        'accessible_date',
        'accessible_time'
    ];

    protected $casts = [
        'due_date'        => 'date',
        'due_time'        => 'datetime:H:i',
        'accessible_date' => 'date',
        'accessible_time' => 'datetime:H:i'
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
        return $this->hasMany(ActivityAttachment::class);
    }
    public function criteria()
{
    return $this->belongsToMany(Criteria::class, 'activity_criteria')
        ->withPivot(['assigned_points'])->withTimestamps();
}

    public function submissions()
    {
        return $this->hasMany(ActivitySubmission::class, 'activity_id', 'id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

