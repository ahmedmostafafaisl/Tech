<?php

namespace App\Http\Requests\ChangeRequestReason;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChangeRequestReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_type' => 'sometimes|string|in:Cancel,Reschedule',
            'reason'      => 'sometimes|string',
            'title_ar'    => 'sometimes|string',
            'title_en'    => 'sometimes|string',
        ];
    }

    public function messages(): array
    {
        return [
            'reason_type.in' => 'Reason type must be Cancel or Reschedule.',
        ];
    }
}
