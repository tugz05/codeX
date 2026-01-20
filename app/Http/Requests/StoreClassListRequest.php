<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'section_id' => 'required',
            'academic_year' => 'required|string|max:20',
            'room' => 'required|string|max:100',
        ];
    }
}
