<?php

namespace App\Http\Requests\ChecklistItem;

use Illuminate\Foundation\Http\FormRequest;

class ReorderChecklistItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_ids' => 'required|array',
            'item_ids.*' => 'integer|distinct',
        ];
    }
}
