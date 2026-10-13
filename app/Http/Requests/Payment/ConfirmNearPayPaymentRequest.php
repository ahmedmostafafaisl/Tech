<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmNearPayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // ownership of the payment/appointment is enforced in the controller (authorizeTechOrFail)
    }

    public function rules(): array
    {
        return [
            // Our own reference for the pending payment created by CreateNearPayPaymentRequest — this,
            // not the transaction_id, is what proves the caller is allowed to touch this payment at all.
            'reference_id'   => 'required|string',
            // NearPay's transaction id, reported by the client after a completed SDK transaction. Treated
            // purely as a claim to go verify — never trusted as proof of payment on its own.
            'transaction_id' => 'required|string',
        ];
    }
}
