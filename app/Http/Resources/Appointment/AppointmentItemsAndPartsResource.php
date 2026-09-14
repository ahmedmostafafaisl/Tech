<?php

namespace App\Http\Resources\Appointment;

use Illuminate\Http\Request;
use App\Models\AppointmentItem;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentItemsAndPartsResource extends JsonResource
{
    public $missingItems;
    public $missingParts;

    // Modify the constructor to accept missing items and parts
    public function __construct($resource, $missingInventory = null)
    {
        parent::__construct($resource);

        // Set the missing items and parts if they exist
        $this->missingItems = collect($missingInventory['missing_items'] ?? []);
        $this->missingParts = collect($missingInventory['missing_parts'] ?? []);
    }

    public function toArray(Request $request): array
    {
        $items = collect();
        $parts = collect();

        if ($this->type === 'installation') {
            // Installation: normal items + parts
            $items = collect($this->items)->map(function ($item) {
                $itemArray = $item->toArray();
                $itemArray['type'] = 'item';
                $itemArray['missing'] = $this->missingItems->firstWhere('id', $item->item_id) ? true : false;

                // Add null fields per item
                $itemArray['check_list'] = null;
                $itemArray['valid_warranty'] = null;
                $itemArray['paid_service'] = null;
                $itemArray['issues_reported_from_client'] = null;
                $itemArray['item_form_type'] = null;
                $itemArray['item_form_status'] = null;
                //
                return $itemArray;
            });

            $parts = collect($this->parts)->map(function ($part) {
                $partArray = $part->toArray();
                $partArray['type'] = 'part';
                $lookupId = $part->part_id ?? $part->id;

                $partArray['missing'] = $this->missingParts->firstWhere('id', $lookupId) ? true : false;
                return $partArray;
            });
        } elseif ($this->type === 'emergency' || $this->type === 'periodic') {
            $isEmergency = $this->type === 'emergency';
            $targetItems = $isEmergency ? $this->emergencyItems : $this->periodicItems;

            $totalPrice = 0;

            $items = collect($targetItems)->map(function ($item) use (&$totalPrice) {
                $itemArray = $item->toArray();

                // Common fields
                $itemArray['check_list'] = is_string($itemArray['check_list']) ? json_decode($itemArray['check_list'], true) : $itemArray['check_list'];
                $itemArray['issues_reported_from_client'] = array_key_exists('issues_reported_from_client', $itemArray)
                    ? (is_string($itemArray['issues_reported_from_client'])
                        ? json_decode($itemArray['issues_reported_from_client'], true)
                        : $itemArray['issues_reported_from_client'])
                    : null;
                $itemArray['type'] = 'item';

                // Warranty checks
                $itemId = $item->item->id ?? null;
                $warrantyYears = $item->item->warranty_period ?? 0;
                $isWarranty = $this->isItemInWarranty($itemId, $warrantyYears);

                $itemArray['valid_warranty'] = $isWarranty;
                $itemArray['paid_service'] = !$isWarranty;

                // Parts mapping
                $itemArray['parts'] = collect($item->parts ?? [])->map(function ($part) use (&$totalPrice, $itemId, $warrantyYears) {
                    $partArray = $part->toArray();
                    $partArray['type'] = 'part';
                    $partArray['real_price'] = $partArray['price'] ?? 0;

                    $lookupId = $part->part_id ?? $part->id;
                    $partArray['missing'] = $this->missingParts->firstWhere('id', $lookupId) ? true : false;

                    $partArray['id'] = $lookupId;
                    unset($partArray['part_id']);

                    // Check warranty again here
                    $isWarranty = $this->isItemInWarranty($itemId, $warrantyYears);
                    $partArray['isWarranty'] = $isWarranty;

                    if ($isWarranty) {
                        $partArray['sub_total_price'] = 0;
                        $partArray['discount'] = 0;
                        $partArray['price'] = 0;
                        $partArray['total_price'] = 0;
                    } else {
                        $quantity = floatval($partArray['quantity'] ?? 1);
                        $price = floatval($partArray['price'] ?? 0);
                        $discountValue = floatval($partArray['discount_value'] ?? 0);
                        $discountType = $partArray['discount_type'] ?? 'fixed';

                        $subTotal = $quantity * $price;

                        $discount = $discountType === 'percentage'
                            ? $subTotal * ($discountValue / 100)
                            : $discountValue;

                        $total = $subTotal - $discount;

                        $partArray['sub_total_price'] = round($subTotal, 2);
                        $partArray['discount'] = round($discount, 2);
                        $partArray['total_price'] = round($total, 2);

                        $totalPrice += $total;
                    }

                    return $partArray;
                });

                return $itemArray;
            });
        }




        $inventory = $items->merge($parts)->values()->map(function ($item) {
            if ($item['type'] === 'item') {
                if ($this->type === 'emergency') {
                    $item['emergency_main_item_id'] =  $item['id'];
                }
                if ($this->type === 'periodic') {
                    $item['periodic_main_item_id'] =  $item['id'];
                }
                $item['id'] = $item['item_id'];

                unset($item['item_id']);
            } elseif ($item['type'] === 'part') {
                $item['id'] = $item['part_id'] ?? $item['id'];
                unset($item['part_id']);
            }
            return $item;
        });

        if ($this->type === 'installation') {
            $totalPrice = $inventory->sum(fn($item) => $item['total_price'] ?? 0);
        }

        return [
            'inventory' => $inventory,
            'total_price' => $totalPrice,


            'appointment_discount' => $this->discount ?? 0,
            'discount_type' => $this->discount_type ?? 0,
        ];
    }

    private function isItemInWarranty($itemId, $warrantyYears): bool
    {
        $installation = AppointmentItem::with('appointment.technician')
            ->where('item_id', $itemId)
            ->first();


        if (!$installation || !$installation->appointment?->appointment_date || !$warrantyYears) {
            return false;
        }

        $installDate = \Carbon\Carbon::parse($installation->appointment->appointment_date);
        $warrantyEndDate = $installDate->addYears($warrantyYears);

        return now()->lessThanOrEqualTo($warrantyEndDate);
    }
}
