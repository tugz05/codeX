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
     * Instructors see all replies
     * Students see public replies and their own private replies
     */
    public function repliesVisibleTo($user)
    {
        $query = $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
        
        if ($user && $user->account_type === 'instructor') {
            // Instructors see all replies
            return $query;
        }
        
        if ($user) {
            // Students see public replies OR their own private replies
            return $query->where(function($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->where('visibility', 'private')
                           ->where('user_id', $user->id);
                  });
            });
        }
        
        // If no user, show only public replies
        return $query->where('visibility', 'public');
    }

    public function classlist(): BelongsTo
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    /**
     * Scope to filter comments based on user role and visibility
     * Instructors see all comments (public and private)
     * Students see public comments and their own private comments
     */
    public function scopeVisibleTo($query, $user)
    {
        if ($user && $user->account_type === 'instructor') {
            // Instructors see all comments (public and private)
            return $query;
        }
        
        if ($user) {
            // Students see public comments OR their own private comments
            return $query->where(function($q) use ($user) {
                $q->where('visibility', 'public')
                  ->orWhere(function($subQ) use ($user) {
                      $subQ->where('visibility', 'private')
                           ->where('user_id', $user->id);
                  });
            });
        }
        
        // If no user, show only public comments
        return $query->where('visibility', 'public');
    }
}
