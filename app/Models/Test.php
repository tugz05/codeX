<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Test extends Model
{
    protected $fillable = [
        'testable_id',
        'testable_type',
        'title',
        'type',
        'description',
        'order',
        'total_points',
    ];

    protected $casts = [
        'order' => 'integer',
        'total_points' => 'integer',
    ];

    public function testable(): MorphTo
    {
        return $this->morphTo();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }
}
