<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AttemptActivity extends Model
{
    protected $fillable = [
        'attemptable_id',
        'attemptable_type',
        'activity_type',
        'description',
        'metadata',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function attemptable(): MorphTo
    {
        return $this->morphTo();
    }
}
