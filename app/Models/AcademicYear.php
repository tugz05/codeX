<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = [
        'semester',
        'start_year',
        'end_year',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
    ];

    public function classlist(): HasMany
    {
        return $this->hasMany(Classlist::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->semester} {$this->start_year}-{$this->end_year}";
    }
}
