<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'invoice_status' => 'required|string',
            'sub_total' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'vat' => 'nullable|numeric',
            'total' => 'required|numeric',
            'invoice_url_pdf' => 'nullable|string',
            'invoice_items' => 'nullable|array',
            'invoice_items.*.item_id' => 'required|integer',
            'invoice_items.*.item_name' => 'required|string',
            'invoice_items.*.item_price' => 'required|numeric',
            'invoice_items.*.item_qty' => 'required|integer',
            'invoice_items.*.item_sub_total' => 'required|numeric',
        ];
    }
}
