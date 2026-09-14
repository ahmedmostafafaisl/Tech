<?php

namespace App\Http\Requests\User\Permissions;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'string',
        ];
    }
}
