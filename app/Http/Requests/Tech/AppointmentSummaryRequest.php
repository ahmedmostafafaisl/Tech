<?php

namespace App\Http\Requests\Tech;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AppointmentSummaryRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $from = $this->input('from');
            $to = $this->input('to');

            if (empty($from) && empty($to)) {
                $validator->errors()->add('date', 'You must provide at least one date (from or to).');
            }
        });
    }
}
