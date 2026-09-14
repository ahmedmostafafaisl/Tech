<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class TodayAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Default tech_id from auth if not provided
        if (empty($this->tech_id)) {
            $this->merge([
                'tech_id' => auth()->user()?->tech_id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'tech_id'     => 'required|integer|exists:users,tech_id',
            'date'        => 'required|date_format:Y-m-d',
            'currentPage' => 'required|integer|min:1',
            'pageSize'    => 'required|integer|min:1|max:100',
            'status'      => 'nullable|string',
            'shift'       => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'tech_id.required'     => 'Technician ID is required.',
            'tech_id.exists'       => 'Technician not found.',
            'date.required'        => 'Date is required.',
            'date.date_format'     => 'Date must be in Y-m-d format.',
            'currentPage.required' => 'Current page is required.',
            'currentPage.min'      => 'Current page must be at least 1.',
            'pageSize.required'    => 'Page size is required.',
            'pageSize.min'         => 'Page size must be at least 1.',
            'pageSize.max'         => 'Page size must not exceed 100.',
        ];
    }
}
