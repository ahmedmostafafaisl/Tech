<?php

namespace App\Http\Requests\Appointment;

use App\Models\Item;
use App\Models\User;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                if (!\App\Models\User::where('id', $value)->where('type', 'customer')->exists()) {
                    $fail('The selected customer must be a valid user of type customer.');
                }
            }],
            'technician_id' => ['required', 'exists:users,id', function ($attribute, $value, $fail) {
                if (!\App\Models\User::where('id', $value)->where('type', 'tech')->exists()) {
                    $fail('The selected technician must be a valid user of type tech.');
                }
            }],
            'type' => 'required|in:installation,periodic,emergency',
            'phone' => 'required|string',
            'whats_app' => 'nullable|string',
            'alternative_number' => 'nullable|string',
            'address_id' => 'required|exists:addresses,id',
            'address' => 'nullable|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'branch' => 'nullable|string',
            'sector' => 'nullable|string',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|string',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'required_with:discount|in:fixed,percentage',
            'paid' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,on_way,on_site,hold,complete,reschedule,cancel',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.serial' => 'nullable|string',
            'items.*.floor' => 'nullable|string',
            'items.*.apart' => 'nullable|string',
            'items.*.room' => 'nullable|string',
            'items.*.code' => 'nullable|string',
            'items.*.name' => 'nullable|string',
            'items.*.quantity' => 'nullable|integer',
            'items.*.description' => 'nullable|string',
            'items.*.image' => 'nullable|image|mimes:jpg,jpeg,png',
            'items.*.price' => 'nullable|numeric',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'required_with:items.*.discount|in:fixed,percentage',
            'items.*.payment_type' => 'nullable|string',
            'items.*.status' => 'nullable|in:pending,completed,canceled',
            'items.*.payment_status' => 'nullable|in:pending,paid,failed',
            'parts' => 'nullable|array',
            'parts.*.part_id' => 'nullable|exists:parts,id',
            'parts.*.serial' => 'nullable|string',
            'parts.*.floor' => 'nullable|string',
            'parts.*.apart' => 'nullable|string',
            'parts.*.room' => 'nullable|string',
            'parts.*.code' => 'nullable|string',
            'parts.*.name' => 'nullable|string',
            'parts.*.quantity' => 'nullable|integer',
            'parts.*.discount' => 'nullable|numeric|min:0',
            'parts.*.discount_type' => 'required_with:parts.*.discount|in:fixed,percentage',
            'parts.*.description' => 'nullable|string',
            'parts.*.image' => 'nullable|image|mimes:jpg,jpeg,png',
            'parts.*.price' => 'nullable|numeric',
            'parts.*.payment_type' => 'nullable|string',
            'parts.*.status' => 'nullable|in:pending,completed,canceled',
            'parts.*.payment_status' => 'nullable|in:pending,paid,failed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $subTotal = 0;

            // ✅ Duplicate item_id check
            $itemIds = collect($items)->pluck('item_id')->filter()->toArray();
            if (count($itemIds) !== count(array_unique($itemIds))) {
                $validator->errors()->add('items', 'Duplicate item_id values are not allowed.');
            }


            // Preload all items at once to avoid N+1 queries
            $itemIds = collect($items)->pluck('item_id')->unique()->toArray();
            $itemsMap = Item::whereIn('id', $itemIds)->get()->keyBy('id');

            foreach ($items as $item) {
                $qty = intval($item['quantity'] ?? 0);
                $itemModel = $itemsMap->get($item['item_id']);

                if (!$itemModel) {
                    $validator->errors()->add("items", "Item with ID {$item['item_id']} not found.");
                    continue;
                }

                $price = floatval($itemModel->price);
                $subTotal += $qty * $price;
            }

            $discount = floatval($this->input('discount', 0));
            $paid = floatval($this->input('paid', 0));
            $totalPrice = $subTotal - $discount;
            $collect = $totalPrice - $paid;

            if ($discount > $subTotal) {
                $validator->errors()->add(
                    'discount',
                    "Discount ($discount) must be less than the sub total ($subTotal) of items."
                );
            }

            if ($paid > $totalPrice) {
                $validator->errors()->add(
                    'paid',
                    "Paid amount ($paid) must be less than the total price ($totalPrice)."
                );
            }

            if ($collect < 0) {
                $validator->errors()->add(
                    'collect',
                    "Collect amount ($collect) cannot be negative. Total price: $totalPrice, Paid: $paid."
                );
            }
        });
    }
}
