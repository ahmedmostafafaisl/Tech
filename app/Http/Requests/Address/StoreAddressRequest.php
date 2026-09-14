<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'branch' => 'nullable|string',
            'sector' => 'nullable|string',
            'status' => 'in:active,inactive',
            'type' => 'in:home,work,other',
            'name' => 'nullable|string',
            'lat' => 'nullable|string',
            'long' => 'nullable|string',
            'location_note' => 'nullable|string',
        ];
    }
}
