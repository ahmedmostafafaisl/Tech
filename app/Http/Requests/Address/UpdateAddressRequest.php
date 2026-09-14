<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'branch' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'type' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'long' => 'nullable|numeric',
            'location_note' => 'nullable|string',
            'created_at' => 'nullable|date',
            'updated_at' => 'nullable|date',
        ];
    }
}
