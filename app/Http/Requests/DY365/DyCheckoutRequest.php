<?php

namespace App\Http\Requests\DY365;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DyCheckoutRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'email' => ['required', 'email'],
            'name' => ['required', 'string'],
            'dob' => ['nullable', 'date'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'shipping_address.zip' => ['nullable', 'string'],

            // 🔹 Payments array
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.reference_id' => [
                'required',
                'unique:dy_payment_links,dy_reference_id',
            ],
            'payments.*.payment_type' => ['required', 'string', 'in:tabby,tamara,clickpay'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],

            // 🔹 Items inside each payment
            'payments.*.items' => ['required', 'array', 'min:1'],
            'payments.*.items.*.name' => ['nullable', 'string'],
            'payments.*.items.*.description' => ['nullable', 'string'],
            'payments.*.items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'payments.*.items.*.price' => ['nullable', 'numeric', 'min:0'],
            'payments.*.items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.items.*.rec_id' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'payments.*.payment_type.in' => 'The payment type must be one of: tabby, tamara, or clickpay.',
            'payments.*.phone.regex' => 'The phone number format is invalid.',
            'payments.*.items.required' => 'Each payment must include at least one item.',
            'payments.*.items.*.name.required' => 'Each item must have a name.',
            'payments.*.items.*.quantity.min' => 'Item quantity must be at least 1.',
            'payments.*.items.*.price.min' => 'Item price must be at least 0.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status'        => false,
                'paymentStatus' => (string)null, // optional
                'referenceId'   => (string)null, // optional
                'error'        => ($validator->errors()->first()), // flat array of messages
            ], 404)
        );
    }
}
