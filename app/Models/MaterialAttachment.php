<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'name',
        'type',
        'url',
        'size',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
