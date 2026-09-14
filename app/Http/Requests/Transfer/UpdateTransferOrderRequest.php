<?php

namespace App\Http\Requests\Transfer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class UpdateTransferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'TransferId' => 'required',
            'type' => 'required|string|in:TechnicianToWarehouse,WarehouseToTechnician,TechnicianToTechnician',
            'TechnicianStatus' => 'required|string|in:Confirmed,Rejected,Drafted',
            'fromWarehouseId' => 'required|string',
            'toWarehouseId' => 'required|string',
            // Only required when type is TechnicianToTechnician.
            'TechnicianType' => 'required|string|in:To,From',

            'items' => 'required|array',
            'items.*.Id' => 'required|integer',
            // Added — referenced in updateStatus()/the SerialNum-count check
            // below, but wasn't actually validated before.
            'items.*.Quantity' => 'required|integer|min:1',
            'items.*.ItemNumber' => 'nullable|string',
            'items.*.SerialNum' => 'nullable|array',
            'items.*.SerialNum.*' => 'nullable|string',
        ];
    }
}
