<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleOrCancelAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:reschedule,cancel,hold,on_way,on_site',
            'reschedule_notes' => 'nullable',
            'cancel_notes' => 'nullable',
            'reason' => 'nullable|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
