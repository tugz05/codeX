<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentSubmissionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_submission_id',
        'name',
        'type',
        'url',
        'size',
        'version',
        'parent_attachment_id',
        'version_notes',
        'is_current',
        'folder_id',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'version' => 'integer',
    ];

    public function submission()
    {
        return $this->belongsTo(AssignmentSubmission::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmissionAttachment::class, 'parent_attachment_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AssignmentSubmissionAttachment::class, 'parent_attachment_id')
            ->orderBy('version', 'desc');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FileFolder::class);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->type === 'application/pdf';
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->type ?? '', 'video/');
    }
}
