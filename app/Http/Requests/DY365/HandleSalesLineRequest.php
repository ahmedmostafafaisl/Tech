<?php

namespace App\Http\Requests\DY365;

use App\Models\Item;
use App\Models\Part;
use App\Models\Appointment;
use App\Models\Warehouse;
use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class HandleSalesLineRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        $action = $this->input('action');

        $rules = [
            'action' => 'required|string|in:add,update,delete',
            'appointment_id' => 'required|exists:appointments,id',
        ];

        if ($action === 'add') {
            // $rules['orderTypeId'] = 'required|string|in:تركيب,خدمات,شكوى,صيانة دورية,صيانة طارئة';
            $rules['items'] = 'required|array|min:1';
            $rules['items.*.orderTypeRecId'] = 'required';
            $rules['items.*.ItemNumber'] = 'required|string';
            $rules['items.*.Quantity'] = 'required|numeric|min:1';
            $rules['items.*.WarrantyStatus'] = 'nullable|in:Yes,No,None';
            $rules['items.*.PaymentMethod'] = 'nullable';
        } elseif ($action === 'update') {
            $rules['salesLines'] = 'required|array|min:1';
            $rules['salesLines.*.sales_line_id'] = 'required|integer|exists:appointment_lines,sales_line_id';
            $rules['salesLines.*.quantity'] = 'required|numeric|min:1';
            $rules['salesLines.*.warrantyStatus'] = 'nullable|in:Yes,No,None';
            $rules['salesLines.*.paymentMethod'] = 'nullable';
        } elseif ($action === 'delete') {
            $rules['salesLines'] = 'required|array|min:1';
            $rules['salesLines.*'] = 'required|integer|exists:appointment_lines,sales_line_id';
        }

        return $rules;
    }

    // public function withValidator(Validator $validator): void
    // {
    //     $action = $this->input('action');

    //     if ($action !== 'add' || $validator->fails()) {
    //         return;
    //     }

    //     $items = $this->input('items', []);
    //     $appointment = Appointment::find($this->appointment_id);

    //     if (!$appointment || !$appointment->technician_id) {
    //         return;
    //     }

    //     $fromWarehouse = $appointment->technician?->warehouse_id;
    //     if (!$fromWarehouse) {
    //         $validator->errors()->add('appointment_id', 'Technician warehouse not found.');
    //         return;
    //     }
    //     $fromWarehouseId = Warehouse::where('invent_location_id', $fromWarehouse)->value('id');

    //     foreach ($items as $index => $line) {

    //         $itemNumber = $line['ItemNumber'] ?? null;
    //         $quantity = $line['Quantity'] ?? 0;


    //         $item = Item::where('item_number', $itemNumber)
    //             ->where('warehouse_id', $fromWarehouseId)
    //             ->first();

    //         $part = Part::where('item_number', $itemNumber)
    //             ->where('warehouse_id', $fromWarehouseId)
    //             ->first();

    //         if (!$item && !$part) {
    //             $validator->errors()->add("items.$index.ItemNumber", "Item number $itemNumber not found in technician warehouse.");
    //             continue;
    //         }

    //         $availableQty = $item ? ($item->quantity ?? 0) : ($part->quantity ?? 0);
    //         $errors = [];

    //         if ($quantity > $availableQty) {
    //             $errors["items.$index.Quantity"] = "Requested quantity ($quantity) for item number $itemNumber exceeds technician stock ($availableQty).";
    //         }

    //         if (!empty($errors)) {
    //             throw \Illuminate\Validation\ValidationException::withMessages($errors);
    //         }
    //     }
    // }
}
