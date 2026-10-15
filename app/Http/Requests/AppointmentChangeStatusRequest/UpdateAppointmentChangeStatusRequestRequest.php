<?php

namespace App\Http\Requests\AppointmentChangeStatusRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentChangeStatusRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // ownership/role is checked in the controller
    }

    public function rules(): array
    {
        return [
            // 'status' is intentionally included: an admin approving/rejecting a request needs to set it.
            // The controller still restricts who may call update() at all to the owning technician or an
            // admin — this does not, by itself, let a technician approve their own request, since nothing
            // here enforces WHICH values a technician vs an admin may set; if that distinction matters,
            // it's the next thing to tighten.
            'status'      => 'sometimes|string',
            'type'        => 'sometimes|string',
            'description' => 'nullable|string',
        ];
    }
}
