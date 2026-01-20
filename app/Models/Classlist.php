<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Classlist extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'section',
        'name',
        'academic_year',
        'room',
        'is_archived'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];
    public $incrementing = false; // Since we're using custom string IDs

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($classList) {
            // Auto-generate a random 10-character ID if not set
            if (empty($classList->id)) {
                $classList->id = self::generateUniqueCode();
            }
        });
    }

    protected static function generateUniqueCode(): string
    {
        do {
            $code = Str::lower(Str::random(5)); // e.g. abcd1234xy
        } while (self::where('id', $code)->exists());

        return $code;
    }
    public function room(): HasOne
    {
        return $this->hasOne(Room::class);
    }
    public function academic_year(): HasOne
    {
        return $this->hasOne(AcademicYear::class, 'foreign_key', 'local_key');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'classlist_user')
            ->withPivot('status', 'joined_at')
            ->withTimestamps();
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'classlist_id', 'id');
    }
}
