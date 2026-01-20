<?php

namespace App\Services;

use App\Models\ClassList;
use Illuminate\Support\Facades\Auth;


class ClassListService
{
    public function create(array $data): ClassList
    {
        return ClassList::create([
            'user_id' => Auth::id(),
            'section' => $data['section'],
            'name' => $data['name'],
            'academic_year' => $data['academic_year'],
            'room' => $data['room'],
        ]);
    }
}
