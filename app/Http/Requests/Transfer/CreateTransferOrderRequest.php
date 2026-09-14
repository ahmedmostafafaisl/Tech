<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransferOrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'type' => 'required|string|in:TechnicianToWarehouse,WarehouseToTechnician,TechnicianToTechnician',
            'TechnicianPersonnelNumber' => 'required|string',
            'fromWarehouseId' => 'required|string',
            'toWarehouseId' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.ItemNumber' => 'required|string',
            'items.*.Quantity' => 'required|numeric|min:1',
        ];
    }
}
