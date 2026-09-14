<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseTransferRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'date' => 'nullable|date',
            'status' => 'nullable|in: processing,received,rejected',

            'transferred_items' => 'nullable|array',
            'transferred_items.*.item_id' => 'required_with:transferred_items|exists:items,id',
            'transferred_items.*.quantity' => 'required_with:transferred_items|integer|min:0',

            'transferred_parts' => 'nullable|array',
            'transferred_parts.*.part_id' => 'required_with:transferred_parts|exists:parts,id',
            'transferred_parts.*.quantity' => 'required_with:transferred_parts|integer|min:0',
        ];
    }
}
