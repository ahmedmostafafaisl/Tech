<?php

namespace App\Http\Requests\InvoiceWhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class ListInvoiceWhatsAppMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already requires auth:sanctum
    }

    public function rules(): array
    {
        return [
            'book_id'         => ['nullable', 'string'],
            'phone'           => ['nullable', 'string'],

            // this attempt's own outcome — exact match, a fixed, known set of values
            'status'          => ['nullable', 'string', 'in:pending,sent,failed'],

            'message_id'      => ['nullable', 'string'],

            // WhatsApp's own async status, filled in later by the shared /WhatsApp/receive webhook —
            // exact match, same fixed set applyDeliveryStatus() ever writes
            'delivery_status' => ['nullable', 'string', 'in:sent,delivered,read,failed'],

            // free-text reason — varies per failure (DY365 down, Meta rejection, a delivery_error string),
            // so a partial match is far more useful than requiring the exact text
            'error'           => ['nullable', 'string'],
            'delivery_error'  => ['nullable', 'string'],

            'date_from'       => ['nullable', 'date'],
            'date_to'         => ['nullable', 'date', 'after_or_equal:date_from'],

            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
            'sort_by'         => ['nullable', 'string', 'in:created_at,updated_at,book_id,status,delivery_status'],
            'sort_direction'  => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
