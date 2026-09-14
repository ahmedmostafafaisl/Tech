<?php

namespace App\Http\Requests\Appointment;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentItemsAndPartsRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,on_way,on_site,hold,complete,reschedule,cancel',
            'discount' => 'nullable|numeric|min:0',
            'type' => 'nullable|in:installation,emergency,periodic',
            'discount_type' => 'required_with:discount|in:fixed,percentage',
            'items' => 'sometimes|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.floor' => 'sometimes|string',
            'items.*.apart' => 'sometimes|string',
            'items.*.room' => 'sometimes|string',
            'items.*.quantity' => 'sometimes|numeric',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'required_with:items.*.discount|in:fixed,percentage',
            'items.*.status' => 'sometimes|in:pending,completed,canceled',
            'items.*.payment_status' => 'sometimes|in:pending,paid,failed',
            'parts' => 'sometimes|array',
            'parts.*.part_id' => 'required|exists:parts,id',
            'parts.*.discount' => 'nullable|numeric|min:0',
            'parts.*.discount_type' => 'required_with:parts.*.discount|in:fixed,percentage',
            'parts.*.quantity' => 'sometimes|numeric',
            'parts.*.status' => 'sometimes|in:pending,completed,canceled',
            'parts.*.payment_status' => 'sometimes|in:pending,paid,failed',
            'parts.*.emergency_main_item_id' => 'nullable:periodic_main_item_id|exists:emergency_main_items,id',
            'parts.*.periodic_main_item_id' => 'nullable:emergency_main_item_id|exists:periodic_main_items,id',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $parts = $this->input('parts', []);
            $subTotal = 0;

            // --- Validate items subtotal ---
            $itemIds = collect($items)->pluck('item_id')->filter()->toArray();
            if (count($itemIds) !== count(array_unique($itemIds))) {
                $validator->errors()->add('items', 'Duplicate item_id values are not allowed.');
            }

            $itemModels = Item::whereIn('id', collect($items)->pluck('item_id')->unique())->get()->keyBy('id');

            foreach ($items as $item) {
                $qty = intval($item['quantity'] ?? 0);
                $itemModel = $itemModels->get($item['item_id']);

                if (!$itemModel) {
                    $validator->errors()->add("items", "Item with ID {$item['item_id']} not found.");
                    continue;
                }

                $price = floatval($itemModel->price);
                $subTotal += $qty * $price;
            }

            // --- Validate financials ---
            $discount = floatval($this->input('discount', 0));
            $paid = floatval($this->input('paid', 0));
            $totalPrice = $subTotal - $discount;
            $collect = $totalPrice - $paid;

            if ($discount > $subTotal) {
                $validator->errors()->add('discount', "Discount ($discount) must be less than subtotal ($subTotal).");
            }

            if ($paid > $totalPrice) {
                $validator->errors()->add('paid', "Paid amount ($paid) must be less than total price ($totalPrice).");
            }

            if ($collect < 0) {
                $validator->errors()->add('collect', "Collect ($collect) cannot be negative.");
            }

            // --- Enforce either emergency_main_item_id or periodic_main_item_id on parts ---
            foreach ($parts as $index => $part) {
                $hasEmergency = isset($part['emergency_main_item_id']);
                $hasPeriodic = isset($part['periodic_main_item_id']);

                if (!$hasEmergency && !$hasPeriodic) {
                    $validator->errors()->add("parts.$index.emergency_main_item_id", "The emergency_main_item_id field is required when periodic_main_item_id is not present.");
                    $validator->errors()->add("parts.$index.periodic_main_item_id", "The periodic_main_item_id field is required when emergency_main_item_id is not present.");
                }

                if ($hasEmergency && $hasPeriodic) {
                    $validator->errors()->add("parts.$index", "Each part must have only one of emergency_main_item_id or periodic_main_item_id, not both.");
                }
            }

            // --- Optional: ensure all parts belong to the same emergency or periodic item ---
            // $emergencyIds = collect($parts)->pluck('emergency_main_item_id')->filter()->unique();
            // if ($emergencyIds->count() > 1) {
            //     $validator->errors()->add('parts', 'All parts must belong to the same emergency_main_item_id.');
            // }

            // $periodicIds = collect($parts)->pluck('periodic_main_item_id')->filter()->unique();
            // if ($periodicIds->count() > 1) {
            //     $validator->errors()->add('parts', 'All parts must belong to the same periodic_main_item_id.');
            // }
        });
    }
}
