<?php

namespace App\Http\Requests\AppointmentTransaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentTransactionSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial' => 'required|string',
        ];
    }
}
