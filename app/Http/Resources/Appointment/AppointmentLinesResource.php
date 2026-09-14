<?php

namespace App\Http\Resources\Appointment;

use App\Models\Item;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentLinesResource extends JsonResource
{
    public $missingItems;
    public $missingParts;

    public function __construct($resource, $missingInventory = null)
    {
        parent::__construct($resource);

        $this->missingItems = collect($missingInventory['missing_items'] ?? []);
        $this->missingParts = collect($missingInventory['missing_parts'] ?? []);
    }

    public function toArray(Request $request): array
    {
        $lines = $this->lines ?? collect();

        $inventory = $lines->map(function ($line) {
            $isItem = $line->line_type === 'item';
            $lookupId = $line->line_id;

            $missing = $isItem
                ? $this->missingItems->firstWhere('id', $lookupId)
                : $this->missingParts->firstWhere('id', $lookupId);

            $model = $isItem
                ? Item::find($lookupId)
                : Part::find($lookupId);

            return [
                // 'line' => $line,
                'id' => $lookupId,
                'appointment_line_id' => $line->id,
                // 'line_id' => $line->id,
                'item_rec_id' => $line->item_rec_id,
                'sales_line_id' => $line->sales_line_id,
                'item_number' => $line->item_number,
                'is_paid' => $line->is_paid,
                'line_type' => $line->type,
                'type' => $line->line_type,
                'name' => optional($model)->name,
                'code' => optional($model)->code,
                'serial' => optional($model)->serial,
                'image' => optional($model)->image,
                'quantity' => $line->quantity,
                'price_before' => $line->warranty_status != 'Yes'
                    ? $line->price
                    : 0,
                'price_after' => $line->warranty_status != 'Yes'
                    ? ($line->price - $line->discount)
                    : 0,

                'discount' => $line->discount,
                'discount_type' => $line->discount_type,
                'total_price_before' => $line->warranty_status != 'Yes'
                    ? (($line->total_amount) + (($line->discount ?? 0) * $line->quantity))
                    : 0,
                'total_price_after' => $line->warranty_status != 'Yes'
                    ? $line->total_amount
                    : 0,

                'missing' => !is_null($missing),

                // Additional fields
                'collect' => (string) (!$line->is_paid && $line->warranty_status !== 'Yes')
                    ? $line->total_amount
                    : 0,
                'sales_history_date' => $line->sales_history_date,
                'warranty_status' => $line->warranty_status,
                'description_common_issues' => $line->description_common_issues,
                'item_common_issues' => $line->item_common_issues,
                'payment_method' => $line->payment_method,
                'conditions' => $line->conditions,
            ];
        })->values();

        return [
            'inventory' => $inventory,
            'total_price_before' => $this->lines
                ? $this->lines
                ->where('warranty_status', '!=', 'Yes')
                ->sum(fn($line) => ($line->price ?? 0) * ($line->quantity ?? 1))
                : 0,
            'appointment_discount' => $this->lines
                ? $this->lines->sum(fn($line) => ($line->discount ?? 0) * ($line->quantity ?? 1))
                : 0,
            'total_price_after' => $this->lines
                ? $this->lines->where('warranty_status', '!=', 'Yes')->sum('total_amount')
                : 0,

            // 'total_price' => $inventory->sum(fn($line) => $line['price'] ?? 0),
            'discount_type' => $this->discount_type ?? 'fixed',
        ];
    }
}
