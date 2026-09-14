<?php

namespace App\Http\Resources\Appointment;

use App\Models\Item;
use App\Models\Part;
use Illuminate\Http\Request;
use App\Models\AppointmentItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;



class AppointmentResource extends JsonResource
{
    public $missingItems;
    public $missingParts;
    public $isMissing;

    public function __construct($resource, $missingInventory = null, $isMissing = false)
    {
        parent::__construct($resource);
        $this->missingItems = collect($missingInventory['missing_items'] ?? []);
        $this->isMissing = $isMissing;
        $this->missingParts = collect($missingInventory['missing_parts'] ?? []);
    }

    public function toArray(Request $request): array
    {
        $isInstallation = $this->type === 'installation';
        $isEmergency = $this->type === 'emergency';
        $isPeriodic = $this->type === 'periodic';

        $missingItems = $this->missingItems;
        $missingParts = $this->missingParts;
        $items = $this->items->map(function ($item) use ($missingItems) {
            $isMissing = $missingItems->first(function ($missing) use ($item) {
                return $missing['item_id'] == $item->item_id   // match by item_id
                    || $missing['line_id'] == ($item->pivot->id ?? $item->id); // match by line_id
            });

            return array_merge($item->toArray(), [
                'type'    => 'item',
                'missing' => $isMissing ? true : false,
            ]);
        });


        $parts = $this->parts->map(function ($part) use ($missingParts) {
            return array_merge($part->toArray(), [
                'type'    => 'part',
                'missing' => $missingParts->firstWhere('part_id', $part->id)
                    || $missingParts->firstWhere('line_id', $part->pivot->id ?? null)
                    ? true : false,
            ]);
        });

        $subTotal = 0;
        $partsSubTotal = 0;
        $itemsSubTotal = 0;
        $allPartsWarranty = true;

        $items = collect();
        $parts = collect();

        if ($isInstallation) {
            // Installation items
            $items = $this->items->map(function ($item) use ($missingItems) {
                return array_merge($item->toArray(), [
                    'type' => 'item',
                    // ✅ Fixed: use item_id instead of id
                    'missing' => $missingItems->firstWhere('line_id', $item->pivot->line_id ?? $item->line_id ?? $item->id) ? true : false,
                    'check_list' => null,
                    'valid_warranty' => null,
                    'paid_service' => null,
                    'issues_reported_from_client' => null,
                    'item_form_type' => null,
                    'item_form_status' => null,
                    'warranty' => null,
                    'isWarranty' => null,
                ]);
            });

            // Installation parts
            $parts = $this->parts->map(function ($part) use ($missingParts, &$partsSubTotal) {
                $qty = floatval($part->quantity ?? 1);
                $price = floatval($part->price ?? 0);
                $sub = $qty * $price;

                $partArray = array_merge($part->toArray(), [
                    'type' => 'part',
                    'id' => $part->part_id ?? $part->id,
                    // ✅ Fixed: use part_id instead of id
                    'missing' => $missingParts->firstWhere('part_id', $part->part_id ?? $part->id) ? true : false,
                    'sub_total_price' => $sub,
                    'total_price' => $sub,
                ]);

                $partsSubTotal += $sub;
                return $partArray;
            });

            $subTotal = $items->sum('total_price');
        } elseif ($isEmergency || $isPeriodic) {
            $targetItems = $isEmergency ? $this->emergencyItems : $this->periodicItems;

            $items = $targetItems->map(function ($item) use (&$partsSubTotal, &$allPartsWarranty, $missingParts) {
                $itemArray = $item->toArray();

                $itemArray['type'] = 'item';
                $itemArray['issues_reported_from_client'] = $this->safeJsonDecode($itemArray['issues_reported_from_client'] ?? null);
                $itemArray['check_list'] = $this->safeJsonDecode($itemArray['check_list'] ?? null);

                $itemArray['isWarranty'] = $this->isItemInWarranty(
                    $item->item_id,
                    $item->item->warranty_period ?? 0
                );

                $itemArray['valid_warranty'] = $itemArray['isWarranty'];
                $itemArray['paid_service'] = !$itemArray['isWarranty'];

                // Emergency/Periodic parts
                $itemArray['parts'] = collect($item->parts ?? [])->map(function ($part) use (
                    &$partsSubTotal,
                    $itemArray,
                    &$allPartsWarranty,
                    $missingParts
                ) {
                    $isWarranty = $itemArray['isWarranty'] ?? false;
                    $qty = floatval($part->quantity ?? 1);
                    $price = floatval($part->price ?? 0);
                    $discountValue = floatval($part->discount_value ?? 0);
                    $discountType = $part->discount_type ?? 'fixed';

                    $sub = $qty * $price;

                    $discount = 0;
                    $total = 0;

                    if (!$isWarranty) {
                        $discount = $discountType === 'percentage'
                            ? ($sub * ($discountValue / 100))
                            : $discountValue;

                        $total = $sub - $discount;
                        $partsSubTotal += $total;
                        $allPartsWarranty = false;
                    }

                    return array_merge($part->toArray(), [
                        'type' => 'part',
                        'id' => $part->part_id ?? $part->id,
                        // ✅ Fixed: use part_id instead of id
                        'missing' => $missingParts->firstWhere('part_id', $part->part_id ?? $part->id) ? true : false,
                        'isWarranty' => $isWarranty,
                        'sub_total_price' => round($isWarranty ? 0 : $sub, 2),
                        'discount' => round($discount, 2),
                        'total_price' => round($isWarranty ? 0 : $total, 2),
                    ]);
                });

                return $itemArray;
            });

            $subTotal = $partsSubTotal;
        }

        // ✅ Calculate discount
        $discountType = $this->discount_type ?? 'fixed';
        $discountValue = floatval($this->discount_value ?? 0);
        $appointmentDiscount = $this->discount_value ?? 0;
        // $subTotal = $this->lines
        //     ? $this->lines->where('is_paid', '!=', '1')->sum('total_amount')
        //     : 0;


        $subTotal = $this->lines
            ? $this->lines
            ->filter(fn($line) => $line->warranty_status !== 'Yes' && $line->is_paid != "1")
            ->sum('total_amount')
            : 0;



        $totalPrice =   $subTotal - $appointmentDiscount;



        $collectValue = $isInstallation
            ? $totalPrice - $this->paid
            : ($allPartsWarranty ? 0 : $this->collect);

        // $hasMissing = $isInstallation
        //     ? $items->contains(fn($item) => $item['missing'] === true)
        //     : $items->flatMap(fn($item) => collect($item['parts'] ?? []))
        //     ->contains(fn($part) => $part['missing'] === true);
        $hasMissing = $this->missingItems->isNotEmpty() || $this->missingParts->isNotEmpty();



        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'rec_id' => $this->rec_id,
            'sales_order_id' => $this->sales_order_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer->username ?? null,
            'technician_id' => $this->technician_id,
            'technician_name' => $this->technician->username ?? null,
            'address_id' => $this->address_id,
            'appointment_num' => $this->appointment_num,
            'type' => $this->type,
            'phone' => $this->phone,
            'whats_app' => $this->whats_app,
            'alternative_number' => $this->alternative_number,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'branch' => $this->branch,
            'sector' => $this->sector,
            'appointment_date' => $this->appointment_date,
            'from' => $this->from,
            'to' => $this->to,
            'appointment_time' => $this->appointment_time,
            'installation_date' => $this->installation_date,
            'sub_total_price' => round($subTotal, 2),
            'discount' => round($appointmentDiscount, 2),
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            // 'total_price' => $this->lines
            //     ? $this->lines->where('warranty_status', '!=', 'Yes')->sum('total_amount')
            //     : 0,
            // 'paid'   => $this->status === 'Completed'
            //     ? $this->total_price
            //     : $this->paid,

            // 'collect' => $this->status === 'Completed'
            //     ? 0
            //     : max(
            //         0,
            //         ($this->lines
            //             ? $this->lines->where('warranty_status', '!=', 'Yes')->sum('total_amount')
            //             : 0) - $this->paid
            //     ),

            // 'new_collects' => $this->status === 'Completed'
            //     ? 0
            //     : max(
            //         0,
            //         ($this->lines
            //             ? $this->lines->where('warranty_status', '!=', 'Yes')->sum('total_amount')
            //             : 0) - $this->paid
            //     ),
            'total_price' => round($totalPrice, 2), // ✅ discount already subtracted
            'paid' => $this->status === 'Completed'
                ? round($totalPrice, 2)
                : round($this->paid, 2),

            'collect' => $this->status === 'Completed'
                ? 0
                : max(0, round($totalPrice - $this->paid, 2)),

            'new_collects' => $this->status === 'Completed'
                ? 0
                : max(0, round($totalPrice - $this->paid, 2)),
            'customer_confirmation_status' => $this->customer_confirmation_status,
            'description_common_issues' => $this->description_common_issues,
            'item_common_issues' => $this->item_common_issues,
            'order_notes' => $this->order_notes,
            'warranty_status' => $this->warranty_status,
            'notes_optional' => $this->notes_optional,

            'status' => $this->status,
            'dy365_status' => $this->dy365_status,
            'dy_completed' => $this->dy_completed,
            'service_type' => $this->service_type,
            'cancel_notes' => $this->cancel_notes,
            'reschedule_notes' => $this->reschedule_notes,

            'billing_status' => $this->status === 'Completed'
                ? 'paid'
                : ($this->collect == 0 ? 'paid' : 'unpaid'),
            'power_socket' => $this->power_socket,
            'items_count' => $this->lines ? $this->lines->count() : 0,

            'missing' => $this->isMissing,
            'customer' => $this->customer,
            // 'complete_notes' => $this->complete,
            'complete_notes' => $this->complete->map(function ($note) {
                return [
                    'id' => $note->id,
                    'notes' => $note->notes ?? null,
                    'created_at' => $note->created_at,
                    'images' => $note->images->map(function ($img) {
                        return [
                            'id' => $img->id,
                            'url' => $this->getImageUrl($img->image),
                        ];
                    }),
                ];
            }),



            // 'complete_images' => $this->complete,
            'technician' => [
                'id'        => $this->technician->id,
                'tech_id'   => $this->technician->tech_id,
                'username'  => $this->technician->username,
                'email'     => $this->technician->email,
                'phone'     => $this->technician->phone,
                'status'    => $this->technician->status,
                'completed_appointments_count' => $this->technician
                    ->technicianAppointments()
                    ->where('type', 'complete')
                    ->count(),
                'non_completed_appointments_count' => $this->technician
                    ->technicianAppointments()
                    ->where('type', '!=', 'complete')
                    ->count(),
            ],

            'lines' => $this->lines->map(function ($line) {
                $isItem = $line->line_type === 'item';
                $model = $isItem
                    ? Item::find($line->line_id)
                    : Part::find($line->line_id);

                return [
                    'id' => $line->id,
                    'item_rec_id' => $line->item_rec_id,
                    'sales_line_id' => $line->sales_line_id,
                    'type' => $line->line_type,
                    'line_type' => $line->type,
                    'line_name' => $model->name ?? null,
                    'item_number' => $line->item_number,
                    'quantity' => $line->quantity,
                    'is_paid' => $line->is_paid,
                    'price' => $line->price,
                    'discount' => $line->discount,
                    'discount_type' => $line->discount_type,
                    'total_amount' => $line->total_amount,
                    'collect' => (string)(!$line->is_paid && $line->warranty_status !== 'Yes')
                        ? $line->total_amount
                        : 0,
                    'sales_history_date' => $line->sales_history_date,
                    'warranty_status' => $line->warranty_status,
                    'description_common_issues' => $line->description_common_issues,
                    'item_common_issues' => $line->item_common_issues,
                    'payment_method' => $line->payment_method,
                    // new fields
                    'check_list' => $this->safeJsonDecode($line->check_list ?? null),
                    'floor' => $line->floor,
                    'apart' => $line->apart,
                    'room' => $line->room
                ];
            })->values(),

            'changeStatusRequests' => $this->changeStatusRequests,
            'dy_response' => $this->dy_response,
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
        return now()->lessThanOrEqualTo($installDate->addYears($warrantyYears));
    }

    private function safeJsonDecode($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    private function getImageUrl($path)
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(100));
        }
        return null;
    }
}
