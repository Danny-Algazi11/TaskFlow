<?php

namespace App\Http\Requests\BoardList;

use Illuminate\Foundation\Http\FormRequest;

class ReorderBoardListsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'list_ids' => 'required|array',
            'list_ids.*' => 'integer|distinct',
        ];
    }
}
