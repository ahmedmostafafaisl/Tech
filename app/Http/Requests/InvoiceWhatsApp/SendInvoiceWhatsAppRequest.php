<?php

namespace App\Http\Requests\InvoiceWhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendInvoiceWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'string'],
            'phone'   => ['required', 'string'],
        ];
    }
}
