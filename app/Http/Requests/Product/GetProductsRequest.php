<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class GetProductsRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'currentPage' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:1000',
            'warehouseId' => 'nullable|string',
            'type' => 'nullable|string|in:Product,SparePart,Bundle',
            'book_id' => 'nullable|string',
            'search' => 'nullable|string',
        ];
    }
}
