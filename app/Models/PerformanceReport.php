<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReport extends Model
{
    protected $fillable = [
        'instructor_id',
        'name',
        'type',
        'file_path',
        'filters',
        'generated_at'
    ];

    protected $casts = [
        'filters' => 'array',
        'generated_at' => 'datetime'
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
