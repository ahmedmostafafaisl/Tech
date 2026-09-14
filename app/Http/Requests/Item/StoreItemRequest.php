<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'serial' => 'nullable|string|unique:items,serial',
            'code' => 'nullable|string|unique:items,code',
            'image' => 'nullable|image|max:2048',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'warranty' => 'required|boolean',
            'warranty_period' => 'nullable|integer|min:0',
        ];
    }
}
