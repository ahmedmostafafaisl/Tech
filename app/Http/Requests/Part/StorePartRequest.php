<?php

namespace App\Http\Requests\Part;

use Illuminate\Foundation\Http\FormRequest;

class StorePartRequest extends FormRequest
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
            'serial' => 'nullable|string|unique:parts,serial',
            'code' => 'nullable|string|unique:parts,code',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
        ];
    }
}
