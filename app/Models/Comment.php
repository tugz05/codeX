<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'user_id',
        'content',
        'parent_id',
        'classlist_id',
        'visibility',
    ];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }
    
    /**
     * Get replies visible to a specific user
     */
    public function repliesVisibleTo($user)
    {
        $query = $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
        
        if ($user && $user->account_type === 'instructor') {
            // Instructors see all replies
            return $query;
        }
        // Students see only public replies
        return $query->where('visibility', 'public');
    }

    public function classlist(): BelongsTo
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    /**
     * Scope to filter comments based on user role and visibility
     * Students see only public comments, instructors see all
     */
    public function scopeVisibleTo($query, $user)
    {
        if ($user && $user->account_type === 'instructor') {
            // Instructors see all comments (public and private)
            return $query;
        }
        // Students see only public comments
        return $query->where('visibility', 'public');
    }
}
