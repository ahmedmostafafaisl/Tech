<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class GetCustomerAddressesRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        // If customer_id not sent, use auth user if type = customer
        if (!$this->has('customer_id') && auth()->check() && auth()->user()->type === 'customer') {
            $this->merge([
                'customer_id' => auth()->id(),
            ]);
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->input('customer_id')) {
                $validator->errors()->add('customer_id', 'Customer ID is required or must be resolved from authenticated user.');
            }
        });
    }
}
