<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class SearchAppointmentTransactionSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'serial.required' => 'Serial is required.',
        ];
    }
}
