<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $itemId = $this->route('id');

        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'serial' => 'sometimes|required|string|unique:items,serial,' . $itemId,
            'code' => 'sometimes|required|string|unique:items,code,' . $itemId,
            'image' => 'sometimes|nullable|image|max:2048',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:0',
            'warranty' => 'sometimes|required|boolean',
            'warranty_period' => 'sometimes|nullable|integer|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
        ];
    }
}
