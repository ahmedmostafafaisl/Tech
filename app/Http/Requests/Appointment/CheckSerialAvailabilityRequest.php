<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class CheckSerialAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial'      => 'required|string',
            'item_number' => 'required|string',
            'tech_id'     => 'required|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'serial.required'      => 'Serial is required.',
            'item_number.required' => 'Item number is required.',
            'tech_id.required'     => 'Tech ID is required.',
        ];
    }
}
