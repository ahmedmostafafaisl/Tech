<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class NewCreateTransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tech_id' => 'required|string',
            'type' => 'required|string|in:TechnicianToWarehouse,WarehouseToTechnician,TechToTech,TechnicianToTechnician',
            'TechnicianPersonnelNumber' => 'required|string',
            'fromWarehouseId' => 'required|string',
            'toWarehouseId' => 'required|string',

            'items' => 'required|array|min:1',
            'items.*.ItemNumber' => 'required|string',
            'items.*.Quantity' => 'required|numeric|min:1',

            // 'items.*.SerialNum' => 'nullable|array',
            // 'items.*.SerialNum.*' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // ── TechnicianToTechnician feature toggle check ─────────────
            if (
                $this->input('type') === 'TechnicianToTechnician' &&
                !\App\Models\Setting::isActive('transfer_technician_to_technician_active')
            ) {
                $validator->errors()->add(
                    'type',
                    'TechnicianToTechnician transfers are currently disabled.'
                );
            }

            $items = $this->input('items', []);
            $itemNumbers = [];

            foreach ($items as $index => $item) {
                $itemNumber = isset($item['ItemNumber']) ? trim((string) $item['ItemNumber']) : null;
                $quantity = (int) ($item['Quantity'] ?? 0);
                $serials = $item['SerialNum'] ?? null;

                // Prevent duplicate ItemNumber across the whole request
                if ($itemNumber !== null && $itemNumber !== '') {
                    if (in_array($itemNumber, $itemNumbers, true)) {
                        $validator->errors()->add(
                            "items.$index.ItemNumber",
                            'ItemNumber must not be duplicated in the request.'
                        );
                    } else {
                        $itemNumbers[] = $itemNumber;
                    }
                }

                if ($serials === null) {
                    continue;
                }

                if (!is_array($serials)) {
                    $validator->errors()->add(
                        "items.$index.SerialNum",
                        'SerialNum must be an array.'
                    );
                    continue;
                }

                // Remove null / empty values
                $serials = array_values(array_filter($serials, function ($serial) {
                    return $serial !== null && trim((string) $serial) !== '';
                }));

                // Quantity must equal number of serials
                if (count($serials) !== $quantity) {
                    $validator->errors()->add(
                        "items.$index.SerialNum",
                        'The number of serial numbers must equal Quantity.'
                    );
                }

                // Prevent duplicate serials inside the same item
                if (count($serials) !== count(array_unique($serials))) {
                    $validator->errors()->add(
                        "items.$index.SerialNum",
                        'Serial numbers must be unique for each item.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Type is required.',
            'type.in' => 'Type must be TechnicianToWarehouse, WarehouseToTechnician, or TechnicianToTechnician.',

            'TechnicianPersonnelNumber.required' => 'TechnicianPersonnelNumber is required.',
            'TechnicianPersonnelNumber.string' => 'TechnicianPersonnelNumber must be a string.',

            'fromWarehouseId.required' => 'fromWarehouseId is required.',
            'fromWarehouseId.string' => 'fromWarehouseId must be a string.',

            'toWarehouseId.required' => 'toWarehouseId is required.',
            'toWarehouseId.string' => 'toWarehouseId must be a string.',

            'items.required' => 'Items are required.',
            'items.array' => 'Items must be an array.',
            'items.min' => 'At least one item is required.',

            'items.*.ItemNumber.required' => 'ItemNumber is required for each item.',
            'items.*.ItemNumber.string' => 'ItemNumber must be a string.',

            'items.*.Quantity.required' => 'Quantity is required for each item.',
            'items.*.Quantity.numeric' => 'Quantity must be numeric.',
            'items.*.Quantity.min' => 'Quantity must be at least 1.',

            'items.*.SerialNum.array' => 'SerialNum must be an array.',
            'items.*.SerialNum.*.string' => 'Each SerialNum must be a string.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tech_id' => $this->input('tech_id') ?? auth()->user()->tech_id,
        ]);
    }
}
