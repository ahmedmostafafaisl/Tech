<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CompleteAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            // 'status' => 'required|in:complete',
            // 'discount' => 'nullable|numeric|min:0',
            // 'discount_type' => 'required_with:discount|in:fixed,percentage',

            'note' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',

            //


            'lines' => 'nullable|array',
            'lines.*.line_id' => 'required|exists:appointment_lines,id',

            'lines.*.check_list' => 'nullable|array',
            'lines.*.floor' => 'nullable|string',
            'lines.*.apart' => 'nullable|string',
            'lines.*.room' => 'nullable|string',
            'lines.*.quantity' => 'nullable|numeric',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.discount_type' => 'nullable_with:lines.*.discount|in:fixed,percentage',
            'lines.*.payment_status' => 'nullable|in:pending,paid,failed',


            // 'items' => 'nullable|array',
            // 'items.*.item_id' => 'required|exists:items,id',

            // 'items.*.check_list' => 'required|array',
            // 'items.*.floor' => 'required|string',
            // 'items.*.apart' => 'required|string',
            // 'items.*.room' => 'required|string',
            // 'items.*.quantity' => 'required|numeric',
            // 'items.*.discount' => 'nullable|numeric|min:0',
            // 'items.*.discount_type' => 'required_with:items.*.discount|in:fixed,percentage',
            // 'items.*.payment_status' => 'required|in:pending,paid,failed',


            // 'parts' => 'required|array',
            // 'parts.*.part_id' => 'required|exists:parts,id',
            // 'parts.*.serial' => 'required|string',
            // 'parts.*.code' => 'required|string',
            // 'parts.*.name' => 'required|string',
            // 'parts.*.description' => 'nullable|string',
            // 'parts.*.image' => 'nullable|image|max:2048',
            // 'parts.*.price' => 'required|numeric',
            // 'parts.*.quantity' => 'required|numeric',
            // 'parts.*.payment_status' => 'required|in:pending,paid,failed',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $itemIds = array_filter(array_column($items, 'item_id'));

            if (count($itemIds) !== count(array_unique($itemIds))) {
                $validator->errors()->add('items', 'Duplicate item_id values are not allowed.');
            }
        });
    }
}
