<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class SearchAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'customer_id' => 'nullable|integer|exists:users,id',
            'technician_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
