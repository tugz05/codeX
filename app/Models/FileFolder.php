<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'folderable_type',
        'folderable_id',
        'user_id',
        'order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FileFolder::class, 'parent_id')->orderBy('order');
    }

    public function folderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignmentSubmissionAttachments(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionAttachment::class);
    }
}
