<?php

namespace App\Http\Requests\BoardList;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoardListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
