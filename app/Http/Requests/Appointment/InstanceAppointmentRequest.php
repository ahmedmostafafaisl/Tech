<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class InstanceAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['periodic', 'emergency', 'installation'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.service_type' => ['required_if:type,periodic'],
            'items.*.issues_reported_from_client' => ['required_if:type,emergency', 'json'],
            'items.*.quantity' => ['required_if:type,installation', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'You must provide at least one item.',
            'items.*.item_id.required' => 'Each item must include an item_id.',
            'items.*.item_id.exists' => 'Item not found.',
            'items.*.service_type.required_if' => 'Service type is required for periodic items.',
            'items.*.issues_reported_from_client.required_if' => 'Client issues are required for emergency items.',
            'items.*.issues_reported_from_client.json' => 'Client issues must be a valid JSON format.',
        ];
    }
}
