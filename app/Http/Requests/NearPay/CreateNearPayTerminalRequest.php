<?php

namespace App\Http\Requests\NearPay;

use Illuminate\Foundation\Http\FormRequest;

class CreateNearPayTerminalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route already requires auth:sanctum + role:super_admin|admin
    }

    public function rules(): array
    {
        return [
            'trsm_code' => ['required', 'string', 'max:255'],
            'name'      => ['required', 'string', 'max:255'],
        ];
    }
}
