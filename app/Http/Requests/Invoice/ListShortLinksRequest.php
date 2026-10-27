<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class ListShortLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // route already requires auth:sanctum
    }

    public function rules(): array
    {
        return [
            'book_id'           => ['nullable', 'string'],
            'phone'             => ['nullable', 'string'],

            'sent'              => ['nullable', 'in:0,1,true,false'],
            'invoice_generated' => ['nullable', 'in:0,1,true,false'],
            'last_error'        => ['nullable', 'string'],
            'code'              => ['nullable', 'string'],
            'hits'              => ['nullable', 'integer', 'min:0'],
            'date_from'         => ['nullable', 'date'],
            'date_to'           => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'          => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'              => ['nullable', 'integer', 'min:1'],
            'sort_by'           => ['nullable', 'string', 'in:created_at,updated_at,hits,sent,invoice_generated,book_id'],
            'sort_direction'    => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
