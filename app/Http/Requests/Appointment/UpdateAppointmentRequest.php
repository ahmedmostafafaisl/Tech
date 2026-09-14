<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'technician_id' => [
                'sometimes',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!\App\Models\User::where('id', $value)->where('type', 'tech')->exists()) {
                        $fail("The selected technician must be a valid user of type 'tech'.");
                    }
                }
            ],
            'customer_id' => [
                'sometimes',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!\App\Models\User::where('id', $value)->where('type', 'customer')->exists()) {
                        $fail("The selected customer must be a valid user of type 'customer'.");
                    }
                }
            ],
            'type' => 'sometimes|in:installation,periodic,emergency',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'whats_app' => 'nullable|string',
            'alternative_number' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'branch' => 'nullable|string',
            'sector' => 'nullable|string',
            'appointment_date' => 'sometimes|date',
            'appointment_time' => 'sometimes|string',
            'total_price' => 'sometimes|numeric',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'required_with:discount|in:fixed,percentage',
            'status' => 'sometimes|in:pending,on_way,on_site,hold,complete,reschedule,cancel,Delayed,Completed,Scheduled',
            'service_type' => 'sometimes|string',
            'address_id' => 'nullable|exists:addresses,id',
            'cancel_notes' => 'nullable|string',
            'reschedule_notes' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.serial' => 'sometimes|string',
            'items.*.floor' => 'sometimes|string',
            'items.*.apart' => 'sometimes|string',
            'items.*.room' => 'sometimes|string',
            'items.*.code' => 'sometimes|string',
            'items.*.name' => 'sometimes|string',
            'items.*.quantity' => 'sometimes|numeric',
            'items.*.description' => 'nullable|string',
            'items.*.image' => 'nullable|image',
            'items.*.price' => 'sometimes|numeric',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'required_with:items.*.discount|in:fixed,percentage',
            'items.*.status' => 'sometimes|in:pending,completed,canceled',
            'items.*.payment_status' => 'sometimes|in:pending,paid,failed',
            'parts' => 'sometimes|array',
            'parts.*.part_id' => 'required|exists:parts,id',
            'parts.*.serial' => 'sometimes|string',
            'parts.*.code' => 'sometimes|string',
            'parts.*.name' => 'sometimes|string',
            'parts.*.description' => 'nullable|string',
            'parts.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'parts.*.price' => 'sometimes|numeric',
            'parts.*.discount' => 'nullable|numeric|min:0',
            'parts.*.discount_type' => 'required_with:parts.*.discount|in:fixed,percentage',
            'parts.*.quantity' => 'sometimes|numeric',
            'parts.*.status' => 'sometimes|in:pending,completed,canceled',
            'parts.*.payment_status' => 'sometimes|in:pending,paid,failed',
        ];
    }
}
