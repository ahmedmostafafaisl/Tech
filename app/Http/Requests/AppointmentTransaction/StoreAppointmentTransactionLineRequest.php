<?php
// ===== StoreAppointmentTransactionLineRequest.php =====

namespace App\Http\Requests\AppointmentTransaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentTransactionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_line_rec_id' => 'required|integer|unique:appointment_transaction_lines,sales_line_rec_id',
            'item_number'       => 'required|string',
            'quantity'          => 'required|integer|min:1',
            'order_type_rec_id' => 'required|integer',
            'warranty_status'   => 'required|in:Yes,No,None',
        ];
    }
}
