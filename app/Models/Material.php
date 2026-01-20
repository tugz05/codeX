<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'classlist_id',
        'title',
        'posted_by',
        'description',
        'type',
        'url',
        'video_url',
        'embed_code',
        'accessible_date',
        'accessible_time',
    ];

    protected $casts = [
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
        return $this->hasMany(MaterialAttachment::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
