<?php

namespace App\Http\Requests\TransmittedSerial;

use Illuminate\Foundation\Http\FormRequest;

class ListTransmittedSerialsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // role-gated at the route (super_admin|admin)
    }

    public function rules(): array
    {
        return [
            'book_id'        => ['nullable', 'string'],
            'tech_id'        => ['nullable', 'string'],
            'item_number'    => ['nullable', 'string'],
            'serial'         => ['nullable', 'string'],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date', 'after_or_equal:date_from'],
            'search'         => ['nullable', 'string'],
            'per_page'       => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'           => ['nullable', 'integer', 'min:1'],
            'sort_by'        => ['nullable', 'string', 'in:created_at,updated_at,book_id,item_number,serial,tech_id'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
