<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentPaymentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'payments' => 'required|array|min:1',
            'payments.*.reference_id' => 'nullable|string',
            'payments.*.payment_type' => 'required|string',
            'payments.*.phone' => 'nullable|string',
            'payments.*.total_price' => 'required|string',
        ];
    }
}
