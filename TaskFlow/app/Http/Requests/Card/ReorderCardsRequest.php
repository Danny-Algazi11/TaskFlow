<?php

namespace App\Http\Requests\Card;

use Illuminate\Foundation\Http\FormRequest;

class ReorderCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_ids' => 'required|array',
            'card_ids.*' => 'integer|distinct',
        ];
    }
}
