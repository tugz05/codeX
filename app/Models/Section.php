<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'schedule_from',
        'schedule_to',
        'day',
    ];

    protected $casts = [
        'schedule_from' => 'string',
        'schedule_to' => 'string',
    ];

    /**
     * Get the user that owns the Section
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classlist(): HasMany
    {
        return $this->hasMany(Classlist::class);
    }

    public function getScheduleAttribute(): string
    {
        return "{$this->day} {$this->schedule_from} - {$this->schedule_to}";
    }
}
