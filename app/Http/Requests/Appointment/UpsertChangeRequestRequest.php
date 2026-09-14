<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpsertChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id'        => 'required|string',
            'sales_order_id' => 'required|string',
            'tech_id'        => 'required',
            'request_type'   => 'required|boolean|in:0,1',
            'reason_rec_id'  => 'nullable|integer',
            'notes'          => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required'        => 'Book ID is required.',
            'sales_order_id.required' => 'Sales order ID is required.',
            'tech_id.required'        => 'Tech ID is required.',
            'request_type.required'   => 'Request type is required.',
            'request_type.in'         => 'Request type must be 0 (Cancel) or 1 (Reschedule).',
        ];
    }
}
