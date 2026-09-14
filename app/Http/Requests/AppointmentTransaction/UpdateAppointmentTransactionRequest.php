<?php
// ===== UpdateAppointmentTransactionRequest.php =====

namespace App\Http\Requests\AppointmentTransaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => 'sometimes|string|unique:appointment_transactions,book_id,' . $this->route('id'),
            'rec_id'  => 'sometimes|integer',
            'tech_id' => 'sometimes|integer',
        ];
    }
}
