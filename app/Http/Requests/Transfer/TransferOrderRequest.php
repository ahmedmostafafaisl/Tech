<?php

namespace App\Http\Requests\Transfer;

use App\Models\Item;
use App\Models\Part;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;

class TransferOrderRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    protected function prepareForValidation()
    {
        // If tech_id is not explicitly provided and the user is authenticated
        if (!$this->has('tech_id') && auth()->check()) {
            // Assuming authenticated user has a `technician` relation
            $this->merge([
                'tech_id' => optional(auth()->user())->id,
            ]);
        }
    }

    public function rules(): array
    {
        // 'TechnicianToWarehouse', 'WarehouseToTechnician', 'TechnicianToTechnician'
        return [
            'type' => 'nullable|in:TechnicianToWarehouse,WarehouseToTechnician,TechnicianToTechnician',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'tech_id' => 'nullable|exists:users,id',
            'date' => 'nullable|date',
            'status' => 'nullable|in:Created,Shipped,Received',
            'technician_status' =>  'nullable|in:Drafted,Confirmed,Rejected',
            'transfer_rec_id' => 'nullable|numeric',
            'transfer_id' => 'nullable|string',
            'from_warehouse' => 'nullable|string',
            'to_warehouse' => 'nullable|string',
            'from_warehouse_rec' => 'nullable|string',
            'to_warehouse_rec' => 'nullable|string',

            'transfer_order_lines' => 'nullable|array|min:1',
            'transfer_order_lines.*.item_number' => 'required',
            'transfer_order_lines.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            $lines = $this->input('transfer_order_lines', []);

            if ($type === 'WarehouseToTechnician') {
                $fromWarehouseId = $this->input('warehouse_id');

                foreach ($lines as $index => $line) {
                    $itemNumber = $line['item_number'] ?? null;
                    $quantity = $line['quantity'] ?? 0;

                    $item = Item::where('item_number', $itemNumber)
                        ->where('warehouse_id', $fromWarehouseId)
                        ->first();

                    $part = Part::where('item_number', $itemNumber)
                        ->where('warehouse_id', $fromWarehouseId)
                        ->first();

                    if (!$item && !$part) {
                        $validator->errors()->add("transfer_order_lines.$index.item_number", "Item number $itemNumber not found in items or parts for this warehouse.");
                        continue;
                    }

                    $availableQty = $item ? ($item->quantity ?? 0) : ($part->quantity ?? 0);

                    if ($quantity > $availableQty) {
                        $validator->errors()->add("transfer_order_lines.$index.quantity", "Requested quantity ($quantity) for item number $itemNumber exceeds available stock ($availableQty) in the warehouse.");
                    }
                }
            }

            if ($type === 'TechnicianToWarehouse') {
                $tech = auth()->user();
                $toWarehouse = Warehouse::where('invent_location_id', $tech->warehouse_id)->first();

                if (!$toWarehouse) {
                    $validator->errors()->add('warehouse_id', 'Technician warehouse not found.');
                    return;
                }

                $fromWarehouseId = $toWarehouse->id;

                foreach ($lines as $index => $line) {
                    $itemNumber = $line['item_number'] ?? null;
                    $quantity = $line['quantity'] ?? 0;

                    $item = Item::where('item_number', $itemNumber)
                        ->where('warehouse_id', $fromWarehouseId)
                        ->first();

                    $part = Part::where('item_number', $itemNumber)
                        ->where('warehouse_id', $fromWarehouseId)
                        ->first();

                    if (!$item && !$part) {
                        $validator->errors()->add("transfer_order_lines.$index.item_number", "Item number $itemNumber not found in technician warehouse.");
                        continue;
                    }

                    $availableQty = $item ? ($item->quantity ?? 0) : ($part->quantity ?? 0);

                    if ($quantity > $availableQty) {
                        $id = $item ? $item->id : ($part ? $part->id : null);
                        $type = $item ? 'item_id' : 'part_id';

                        $validator->errors()->add(
                            "transfer_order_lines.$index.quantity",
                            "Requested quantity ($quantity) for item number $itemNumber exceeds technician stock ($availableQty). [$type: $id]"
                        );
                    }
                }
            }
        });
    }
}
