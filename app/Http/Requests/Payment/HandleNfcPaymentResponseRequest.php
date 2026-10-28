<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class HandleNfcPaymentResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // ownership of the payment/appointment is enforced in the controller (authorizeTechOrFail)
    }

    public function rules(): array
    {
        return [
            // The reference_id generated and returned by sendPaymentLinks for this NFC payment — not
            // NearPay's own transaction id, and what actually proves the caller is allowed to touch this
            // payment at all.
            'customer_reference_number'  => 'required|string',
            // NearPay's transaction id, used to verify this transaction server-side via GET /transactions/{id}.
            'payment_id'                 => 'required|string',
            // Card-network-level identifier NearPay's SDK reports after the tap completes — recorded as-is,
            // not used for verification (verification is by payment_id against NearPay's own API only).
            'retrieval_reference_number' => 'required|string',
        ];
    }
}
