<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $userId = $this->route('user');
        $userId = is_object($userId) ? $userId->id : $userId;

        return [
            'username' => 'sometimes|string',
            'email'    => 'sometimes|email|unique:users,email,' . $userId,
            'phone'    => 'sometimes|string|unique:users,phone,' . $userId,
            'pin_code' => 'sometimes|string|min:4|max:6',
            'password' => 'sometimes|string|min:6',
            'image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'role'     => 'sometimes|exists:roles,name',
            'status' => 'nullable|in:active,inactive',
            'tech_id' => 'sometimes',
            'technician_rec_id' => 'sometimes',
            'warehouse_id' => 'sometimes|string',

        ];
    }
}
