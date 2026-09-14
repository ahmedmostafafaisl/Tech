<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class GetTamaraPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'     => 'nullable|string|required_without:reference_id',
            'reference_id' => 'nullable|string|required_without:order_id',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required_without'     => 'Provide either order_id or reference_id.',
            'reference_id.required_without' => 'Provide either order_id or reference_id.',
        ];
    }
}
