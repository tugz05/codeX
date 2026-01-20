<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id', // <-- Added here
        'name',
        'type',
        'url',
        'size',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
