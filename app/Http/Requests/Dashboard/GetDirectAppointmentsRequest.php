<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class GetDirectAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tech_id'        => 'nullable|integer|exists:users,tech_id',
            'sales_order_id' => 'nullable|string',
            'book_id'         => 'nullable|string',
            'from_date'      => 'nullable|date_format:Y-m-d',
            'to_date'        => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'date'           => 'nullable|date_format:Y-m-d',
            'status'         => 'nullable|string',
            'currentPage'    => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:1000',
        ];
    }
}
