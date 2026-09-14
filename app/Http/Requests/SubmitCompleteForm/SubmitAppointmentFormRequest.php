<?php

namespace App\Http\Requests\SubmitCompleteForm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitAppointmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sanctum middleware will protect
    }

    public function rules(): array
    {
        return [
            'type_code' => ['required', 'string', 'max:50', Rule::in(['periodic', 'service', 'emergency'])],

            'sales_order_id' => ['required', 'string', 'max:100'],
            'book_id' => ['required', 'string', 'max:100'],
            'appointment_id' => ['nullable', 'integer', 'min:1'],


            // periodic/service
            'option_id' => ['required_if:type_code,periodic,service', 'integer', 'min:1'],

            // emergency
            'device_id' => ['required_if:type_code,emergency', 'integer', 'min:1'],
            'problem_id' => ['required_if:type_code,emergency', 'integer', 'min:1'],
            'solution_id' => ['required_if:type_code,emergency', 'integer', 'min:1'],

            // dynamic values + files
            'values' => ['nullable', 'array'],
            'files' => ['nullable', 'array'],
            'values.note_other' => ['nullable', 'string', 'max:255'],

            // allow multiple files per key: files[field_key][]
            'files.*' => ['nullable'],
        ];
    }
}
