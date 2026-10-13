<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CreateNearPayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // ownership of the appointment is enforced in the controller (authorizeTechOrFail)
    }

    public function rules(): array
    {
        return [
            'book_id'        => 'required|string',
            'sales_order_id' => 'required|string',
            // What the technician entered on-device before tapping the card. Validated server-side against
            // the appointment's actual outstanding balance in the controller — never trusted on its own.
            'amount'         => 'required|numeric|min:0.01',
            'phone'          => 'nullable|string',
        ];
    }
}
