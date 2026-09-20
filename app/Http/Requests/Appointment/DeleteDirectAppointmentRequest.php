<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class DeleteDirectAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'Book ID is required.',
        ];
    }
}
