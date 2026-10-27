<?php

namespace App\Http\Requests\NearPay;

use Illuminate\Foundation\Http\FormRequest;

class AssignNearPayTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route already requires auth:sanctum + role:super_admin|admin
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
