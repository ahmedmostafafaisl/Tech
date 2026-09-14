<?php

namespace App\Http\Requests\Part;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $partId = $this->route('id');
        return [
            'category_id' => 'required|exists:categories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'serial' => 'sometimes|string|unique:parts,serial,' .   $partId,
            'code' => 'sometimes|string|unique:parts,code,' .   $partId,
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
        ];
    }
}
