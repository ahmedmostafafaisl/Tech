<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['nullable', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'product'       => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_number.unique' => 'This order number has already been registered.',
        ];
    }
}
