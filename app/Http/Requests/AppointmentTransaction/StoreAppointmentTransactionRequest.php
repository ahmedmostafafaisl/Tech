<?php
// ===== StoreAppointmentTransactionRequest.php =====

namespace App\Http\Requests\AppointmentTransaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id'                          => 'required|string|unique:appointment_transactions,book_id',
            'rec_id'                           => 'required|integer',
            'tech_id'                          => 'required|integer',
            'lines'                            => 'required|array|min:1',
            'lines.*.sales_line_rec_id'        => 'required|integer|unique:appointment_transaction_lines,sales_line_rec_id',
            'lines.*.item_number'              => 'required|string',
            'lines.*.quantity'                 => 'required|integer|min:1',
            'lines.*.order_type_rec_id'        => 'required|integer',
            'lines.*.warranty_status'          => 'required|in:Yes,No,None',
            'lines.*.serials'                  => 'nullable|array',
            'lines.*.serials.*.serial'         => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.unique'                       => 'This book ID already exists.',
            'lines.required'                       => 'At least one line is required.',
            'lines.*.sales_line_rec_id.unique'     => 'Sales line rec ID already exists.',
            'lines.*.warranty_status.in'           => 'Warranty status must be Yes, No, or None.',
        ];
    }
}
