<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class AttachEmergencyItemRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'items' => 'required|array',
            'items.*.type' => 'required|in:periodic,emergency',
            'items.*.maintenance_type' => 'nullable|string', // conditional
            'items.*.issues_reported_from_client' => 'nullable|json', // conditional
            'items.*.appointment_id' => 'required|exists:appointments,id',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.serial' => 'nullable|string',
            'items.*.floor' => 'nullable|string',
            'items.*.apart' => 'nullable|string',
            'items.*.room' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer',
            'items.*.status' => 'nullable|in:pending,completed,canceled',
            'items.*.payment_type' => 'nullable|in:cash,online_payment,tabby,tamara,apply_pay,pay_with_package',
            'items.*.payment_status' => 'nullable|in:pending,paid,refunded,unpaid',
            'items.*.check_list' => 'nullable|json',
            'items.*.valid_warranty' => 'nullable|boolean',
            'items.*.paid_service' => 'nullable|boolean',
            'items.*.missing' => 'nullable|boolean',
            'items.*.item_form_type' => 'nullable|in:return_for_refund,collect_for_maintenance,replace_with_new',
            'items.*.item_form_status' => 'nullable|in:pending,rejected,approved',

            'items.*.parts' => 'nullable|array',
            'items.*.parts.*.part_id' => 'required|exists:parts,id',
            'items.*.parts.*.floor' => 'nullable|string',
            'items.*.parts.*.apart' => 'nullable|string',
            'items.*.parts.*.room' => 'nullable|string',
            'items.*.parts.*.quantity' => 'nullable|integer',
            'items.*.parts.*.discount' => 'nullable|numeric',
            'items.*.parts.*.discount_type' => 'nullable|in:fixed,percentage',
            'items.*.parts.*.discount_value' => 'nullable|numeric',
            'items.*.parts.*.status' => 'nullable|in:pending,completed,canceled',
            'items.*.parts.*.payment_type' => 'nullable|in:cash,online_payment,tabby,tamara,apply_pay,pay_with_package',
            'items.*.parts.*.payment_status' => 'nullable|in:pending,paid,refunded,unpaid',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $types = collect($items)->pluck('type')->unique();

            // Enforce uniform 'type' across all items
            if ($types->count() > 1) {
                $validator->errors()->add('items', 'All items must have the same type (either all emergency or all periodic).');
                return;
            }

            $type = $types->first();

            foreach ($items as $index => $item) {
                if ($type === 'periodic' && empty($item['maintenance_type'])) {
                    $validator->errors()->add("items.$index.maintenance_type", 'The maintenance_type field is required for periodic items.');
                }

                if ($type === 'emergency' && empty($item['issues_reported_from_client'])) {
                    $validator->errors()->add("items.$index.issues_reported_from_client", 'The issues_reported_from_client field is required for emergency items.');
                }
            }
        });
    }
}
