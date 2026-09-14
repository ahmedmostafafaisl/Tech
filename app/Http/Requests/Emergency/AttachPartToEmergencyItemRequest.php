<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class AttachPartToEmergencyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.appointment_id' => 'required|exists:appointments,id',
            '*.part_id' => 'required|exists:parts,id',
            '*.part_quantity' => 'required|integer|min:1',
            '*.discount' => 'nullable|numeric|min:0',
            '*.discount_type' => 'nullable|in:fixed,percentage',

            // Just basic exists, no logic here — logic is in `withValidator`
            '*.emergency_main_item_id' => 'nullable|exists:emergency_main_items,id',
            '*.periodic_main_item_id' => 'nullable|exists:periodic_main_items,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->all() as $index => $input) {
                $hasEmergency = isset($input['emergency_main_item_id']);
                $hasPeriodic = isset($input['periodic_main_item_id']);

                if (! $hasEmergency && ! $hasPeriodic) {
                    $validator->errors()->add("$index.emergency_main_item_id", 'Either emergency_main_item_id or periodic_main_item_id is required.');
                    $validator->errors()->add("$index.periodic_main_item_id", 'Either emergency_main_item_id or periodic_main_item_id is required.');
                }

                if ($hasEmergency && $hasPeriodic) {
                    $validator->errors()->add("$index.emergency_main_item_id", 'Only one of emergency_main_item_id or periodic_main_item_id is allowed.');
                    $validator->errors()->add("$index.periodic_main_item_id", 'Only one of emergency_main_item_id or periodic_main_item_id is allowed.');
                }
            }
        });
    }
}
