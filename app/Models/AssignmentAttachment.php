<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssignmentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'name',
        'type',
        'url',
        'size',
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
