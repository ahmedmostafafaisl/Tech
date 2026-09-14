<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class PreAppointmentMessageIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone'             => 'nullable|string',
            'sales_order'       => 'nullable|string',
            'book_id'           => 'nullable|string',
            'from_date'         => 'nullable|date_format:Y-m-d',
            'to_date'           => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'date'         => 'nullable|date_format:Y-m-d',
            'type' => 'nullable|string',
            'is_sent'           => 'nullable|boolean',
            'customer_response' => 'nullable|in:pending,confirm,reschedule,cancel',
            'per_page'          => 'nullable|integer|min:1|max:1000',
            'currentPage'       => 'nullable|integer|min:1',
        ];
    }
}
