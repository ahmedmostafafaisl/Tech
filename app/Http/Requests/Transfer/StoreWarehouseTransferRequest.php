<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseTransferRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'type' => 'in:tech_to_warehouse,warehouse_to_tech',
            'requested_items' => 'array',
            'requested_items.*.item_id' => 'required|integer|exists:items,id',
            'requested_items.*.quantity' => 'required|integer|min:1',
            'requested_parts' => 'array',
            'requested_parts.*.part_id' => 'required|integer|exists:parts,id',
            'requested_parts.*.quantity' => 'required|integer|min:1',
        ];
    }
}
