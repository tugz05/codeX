<?php

namespace App\Models\Student;

use App\Models\Classlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ClasslistUser extends Model
{
    use HasFactory;

    protected $table = 'classlist_user';

    protected $fillable = [
        'classlist_id',
        'user_id',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    // Relationships
    public function classlist()
    {
        return $this->belongsTo(Classlist::class, 'classlist_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
