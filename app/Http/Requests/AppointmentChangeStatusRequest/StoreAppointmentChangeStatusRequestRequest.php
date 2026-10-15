<?php

namespace App\Http\Requests\AppointmentChangeStatusRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentChangeStatusRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // the requesting technician must own the appointment — checked in the controller
    }

    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'type'           => 'required|string',
            'description'    => 'nullable|string',
            // technician_id, customer_id and sales_order_id are deliberately NOT accepted here — they are
            // derived server-side from the appointment record, never trusted from the request body (a
            // technician could otherwise create a request that appears to belong to someone else's job).
        ];
    }
}
