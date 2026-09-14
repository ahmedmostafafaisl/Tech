<?php
// ===== StoreChangeRequestReasonRequest.php =====

namespace App\Http\Requests\ChangeRequestReason;

use Illuminate\Foundation\Http\FormRequest;

class StoreChangeRequestReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_rec_id' => 'required|integer|unique:change_request_reasons,reason_rec_id',
            'reason_type'   => 'required|string|in:Cancel,Reschedule',
            'reason'        => 'required|string',
            'title_ar'      => 'nullable|string',
            'title_en'      => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'reason_rec_id.required' => 'Reason record ID is required.',
            'reason_rec_id.unique'   => 'This reason already exists.',
            'reason_type.in'         => 'Reason type must be Cancel or Reschedule.',
            'reason.required'        => 'Reason text is required.',
        ];
    }
}
