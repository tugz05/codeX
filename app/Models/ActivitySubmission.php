<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'classlist_id',
        'activity_id',
        'user_id',
        'language',
        'code',
        'status',
        'score',
        'feedback',
        'runtime_ms',
        'memory_kb',
        'submitted_at',
        // OPTIONAL if you store runner stdout/stderr/exit:
        // 'stdout', 'stderr', 'exit_code', 'time_ms',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'score'        => 'integer',
        'runtime_ms'   => 'integer',
        'memory_kb'    => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function classlist()
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- NEW: AI evaluations
    public function aiEvaluations()
    {
        return $this->hasMany(AiEvaluation::class, 'submission_id');
    }

    public function latestAiEvaluation()
    {
        return $this->hasOne(AiEvaluation::class, 'submission_id')->latestOfMany();
    }

    // Helpful scopes
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForActivity($query, int $activityId)
    {
        return $query->where('activity_id', $activityId);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
