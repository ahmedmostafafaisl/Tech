<?php

namespace App\Http\Requests\Emergency;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmergencyItemRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'item_id' => 'required|exists:items,id',
            'serial' => 'nullable|string',
            'floor' => 'nullable|string',
            'apart' => 'nullable|string',
            'room' => 'nullable|string',
            'code' => 'nullable|string',
            'name' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'price' => 'required|numeric',
            'sub_total_price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|in:fixed,percentage',
            'discount_value' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
            'status' => 'nullable|in:pending,completed,canceled',
            'payment_type' => 'nullable|in:cash,online_payment,tabby,tamara,apply_pay,pay_with_package',
            'payment_status' => 'nullable|in:pending,paid,refunded,unpaid',
            'check_list' => 'nullable|json',
            'valid_warranty' => 'nullable|boolean',
            'paid_service' => 'nullable|boolean',
            'missing' => 'nullable|boolean',
            'issues_reported_from_client' => 'nullable|json',
            'item_form_type' => 'nullable|in:return,collect,replace',
            'item_form_status' => 'nullable|in:pending,rejected,approved',

            'parts' => 'nullable|array',
            'parts.*.part_id' => 'required|exists:parts,id',
            'parts.*.serial' => 'nullable|string',
            'parts.*.floor' => 'nullable|string',
            'parts.*.apart' => 'nullable|string',
            'parts.*.room' => 'nullable|string',
            'parts.*.code' => 'nullable|string',
            'parts.*.name' => 'nullable|string',
            'parts.*.quantity' => 'nullable|integer',
            'parts.*.description' => 'nullable|string',
            'parts.*.image' => 'nullable|string',
            'parts.*.price' => 'required|numeric',
            'parts.*.sub_total_price' => 'nullable|numeric',
            'parts.*.discount' => 'nullable|numeric',
            'parts.*.discount_type' => 'nullable|in:fixed,percentage',
            'parts.*.discount_value' => 'nullable|numeric',
            'parts.*.total_price' => 'nullable|numeric',
            'parts.*.status' => 'nullable|in:pending,completed,canceled',
            'parts.*.payment_type' => 'nullable|in:cash,online_payment,tabby,tamara,apply_pay,pay_with_package',
            'parts.*.payment_status' => 'nullable|in:pending,paid,refunded,unpaid',
        ];
    }
}
