<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class GetTabbyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id'   => 'nullable|string|required_without:reference_id',
            'reference_id' => 'nullable|string|required_without:payment_id',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required_without'   => 'Provide either payment_id or reference_id.',
            'reference_id.required_without' => 'Provide either payment_id or reference_id.',
        ];
    }
}
