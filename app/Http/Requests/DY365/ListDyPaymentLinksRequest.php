<?php

namespace App\Http\Requests\DY365;

use Illuminate\Foundation\Http\FormRequest;

class ListDyPaymentLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // role-gated at the route (super_admin|admin)
    }

    public function rules(): array
    {
        return [
            'payment_method'       => ['nullable', 'string', 'in:tabby,tamara,clickpay'],
            'status'               => ['nullable', 'string'],
            'book_id'              => ['nullable', 'string'],
            'customer_account'     => ['nullable', 'string'],
            'phone'                => ['nullable', 'string'],
            'dy_reference_id'      => ['nullable', 'string'],
            'payment_reference_id' => ['nullable', 'string'],
            'date_from'            => ['nullable', 'date'],
            'date_to'              => ['nullable', 'date', 'after_or_equal:date_from'],
            'search'               => ['nullable', 'string'],
            'per_page'             => ['nullable', 'integer', 'min:1', 'max:1000'],
            'page'                 => ['nullable', 'integer', 'min:1'],
            'sort_by'              => ['nullable', 'string', 'in:created_at,updated_at,amount,status'],
            'sort_direction'       => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
