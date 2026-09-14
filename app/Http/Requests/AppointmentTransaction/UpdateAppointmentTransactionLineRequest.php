<?php

namespace App\Http\Requests\AppointmentTransaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentTransactionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_line_rec_id' => 'sometimes|integer|unique:appointment_transaction_lines,sales_line_rec_id,' . $this->route('lineId'),
            'item_number'       => 'sometimes|string',
            'quantity'          => 'sometimes|integer|min:1',
            'order_type_rec_id' => 'sometimes|integer',
            'warranty_status'   => 'sometimes|in:Yes,No,None',
        ];
    }
}
