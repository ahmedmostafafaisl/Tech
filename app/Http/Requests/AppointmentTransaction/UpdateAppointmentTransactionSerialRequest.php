<?php

namespace App\Http\Requests\AppointmentTransaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentTransactionSerialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'serial' => 'sometimes|string',
        ];
    }
}
