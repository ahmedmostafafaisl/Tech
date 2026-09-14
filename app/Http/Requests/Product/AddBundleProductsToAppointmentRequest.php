<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class AddBundleProductsToAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'bundleId' => 'required|string|max:255',
            'sales_order_id' => 'required|string|max:255',
            'appointment_id' => 'required',
            'quantity' => 'required|numeric|min:1',
            'tech_id' => 'nullable|integer',
            'book_id' => 'required|string|max:255',
            'OrderTypeRecId' => 'required',
        ];
    }
}
