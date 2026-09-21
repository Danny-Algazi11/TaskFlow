<?php

namespace App\Http\Requests\WorkspaceInvitation;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role' => 'required|in:admin,member',
        ];
    }
}
