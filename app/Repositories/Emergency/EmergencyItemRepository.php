<?php


namespace App\Repositories\Emergency;

use App\Models\Item;
use App\Models\Part;
use App\Models\Appointment;
use App\Models\AppointmentItem;
use App\Models\PeriodicMainItem;
use App\Helper\ApiResponseHelper;
use App\Models\EmergencyMainItem;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodicMainItemPart;
use Illuminate\Support\Facades\Auth;
use App\Models\EmergencyMainItemPart;
use App\Http\Resources\Emergency\EmergencyMainItemResource;
use App\Repositories\Interfaces\EmergencyItemRepositoryInterface;

class EmergencyItemRepository implements EmergencyItemRepositoryInterface
{
    use ApiResponseHelper;

    public function store(array $data, int $appointmentId)
    {
        $authUser = Auth::user();
        // Check if the user is the assigned technician OR has a specific permission
        $appointment = Appointment::findOrFail($appointmentId);
        if (
            $appointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('create emergency_items')
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to create this appointment items .')->send();
        }
        // Ensure the main item gets the correct appointment_id
        $data['appointment_id'] = $appointmentId;

        // Create the main item
        $mainItem = EmergencyMainItem::create($data);

        // Attach parts
        foreach ($data['parts'] as $part) {
            $part['appointment_id'] = $appointmentId;
            $part['emergency_main_item_id'] = $mainItem->id; // if there's a foreign key
            EmergencyMainItemPart::create($part);
        }

        $item =  $mainItem->load('parts');
        return $this->setCode(code: 200)->setData(new EmergencyMainItemResource($item))->setMessage('Success.')->send();
    }


    public function update(int $appointmentId, array $data)
    {
        $authUser = Auth::user();
        // Check if the user is the assigned technician OR has a specific permission
        $appointment = Appointment::findOrFail($appointmentId);
        if (
            $appointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('update emergency_items')
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to update this appointment items .')->send();
        }
        return DB::transaction(function () use ($appointmentId, $data) {
            /** @var EmergencyMainItem $main */
            $main = EmergencyMainItem::where('appointment_id', $appointmentId)->firstOrFail();

            $partsData = $data['parts'] ?? [];
            unset($data['parts']);

            $main->update($data);

            $existingIds = [];
            foreach ($partsData as $partData) {
                $partData['appointment_id'] = $appointmentId;

                if (isset($partData['id'])) {
                    $part = EmergencyMainItemPart::where('id', $partData['id'])
                        ->where('appointment_id', $appointmentId)
                        ->first();
                    if ($part) {
                        $part->update($partData);
                        $existingIds[] = $part->id;
                        continue;
                    }
                }

                // Optionally link to the main item's ID if needed
                $partData['emergency_main_item_id'] = $main->id;
                $newPart = EmergencyMainItemPart::create($partData);
                $existingIds[] = $newPart->id;
            }

            // Remove parts not included in this update
            EmergencyMainItemPart::where('appointment_id', $appointmentId)
                ->whereNotIn('id', $existingIds)
                ->delete();

            $item = $main->load('parts');
            return $this->setCode(code: 200)->setData(new EmergencyMainItemResource($item))->setMessage('Success.')->send();
        });
    }


    public function getItemsByAppointment(int $appointmentId)
    {
        // Authorization: check tech and type
        $authUser = Auth::user();
        $appointment = Appointment::findOrFail($appointmentId);
        if ($appointment->technician_id !== $authUser->id &&   !$authUser->hasPermissionTo('view emergency_items')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to show this appointment items. ')
                ->send();
        }

        $items = EmergencyMainItem::with('parts')
            ->where('appointment_id', $appointmentId)
            ->get();

        return $this->setCode(code: 200)->setData(EmergencyMainItemResource::collection($items))->setMessage('Success.')->send();
    }

    public function show($id)
    {

        $item = EmergencyMainItem::with('parts')->findOrFail($id);
        // Authorization: check tech and type
        $authUser = Auth::user();
        $appointment = Appointment::findOrFail($item->appointment_id);
        if ($appointment->technician_id !== $authUser->id &&   !$authUser->hasPermissionTo('view emergency_items')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to show this appointment item.')
                ->send();
        }
        return $this->setCode(code: 200)->setData(new EmergencyMainItemResource($item))->setMessage('Success.')->send();
    }

    public function getItemHistory($itemId)
    {
        $item = Item::findOrFail($itemId);

        // Get installation (should be one-to-one)
        $installation = AppointmentItem::with('appointment.technician')
            ->where('item_id', $itemId)
            ->first();

        // Get maintenance records (can be multiple)
        $maintenanceItems = EmergencyMainItem::with(['appointment.technician', 'parts'])
            ->where('item_id', $itemId)
            ->orderBy('created_at')
            ->get();

        // Get periodic maintenance records
        $periodicItems = PeriodicMainItem::with(['appointment.technician', 'parts'])
            ->where('item_id', $itemId)
            ->orderBy('created_at')
            ->get();

        // Initialize history array
        $itemHistory = [];

        // Add installation as the first record (if exists)
        if ($installation) {
            $itemHistory[] = [
                'maintenance_type_name' => 'Installation',
                'installed_by' => optional($installation->appointment->technician)->username ?? 'N/A',
                'description' => $installation->description,
                'date' => $installation->appointment->appointment_date ?? '',
                'time' => $installation->appointment->appointment_time ?? '',
                'applied_parts' => [], // you can fill this if you store parts in installation too
            ];
        }

        // Add maintenance records
        foreach ($maintenanceItems as $maintenance) {
            $itemHistory[] = [
                'maintenance_type_name' => 'Emergency Maintenance',
                'maintained_by' => optional($maintenance->appointment->technician)->username ?? 'N/A',
                'description' => $maintenance->description,
                'date' => $maintenance->appointment->appointment_date ?? '',
                'time' => $maintenance->appointment->appointment_time ?? '',
                'applied_parts' => $maintenance->parts->map(function ($part) {
                    return [
                        'part_id' => $part->id,
                        'part_name' => $part->name ?? '',
                        'part_quantity' => $part->quantity ?? 1,
                        'part_image' => $part->image ?? '',
                    ];
                })->toArray(),
            ];
        }


        // Add periodic maintenance records
        foreach ($periodicItems as $maintenance) {
            $itemHistory[] = [
                'maintenance_type_name' => 'Periodic Maintenance',
                'maintained_by' => optional($maintenance->appointment->technician)->username ?? 'N/A',
                'maintenance_type' => $maintenance->maintenance_type,
                'description' => $maintenance->description,
                'date' => $maintenance->appointment->appointment_date ?? '',
                'time' => $maintenance->appointment->appointment_time ?? '',
                'applied_parts' => $maintenance->parts->map(function ($part) {
                    return [
                        'part_id' => $part->id,
                        'part_name' => $part->name ?? '',
                        'part_quantity' => $part->quantity ?? 1,
                        'part_image' => $part->image ?? '',
                    ];
                })->toArray(),
            ];
        }

        // Sort history by date and time
        $itemHistory = collect($itemHistory)->sortBy([
            ['date', 'asc'],
            ['time', 'asc'],
        ])->values()->toArray();


        return $this->setCode(code: 200)->setData([
            'installation_date' => $installation->appointment->appointment_date ?? null,
            'appointment_id' => $installation->appointment_id ?? null,
            'amount' => $installation->total_price ?? null,
            'item_history' => $itemHistory,
        ])->setMessage('Success.')->send();
    }

    public function getClientItems($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $customerId = $appointment->customer_id;
        if (!$customerId) {
            return $this->setCode(code: 404)->setData([])->setMessage('Customer not found for this appointment.')->send();
        }

        $installedItems = AppointmentItem::with(['item', 'appointment'])
            ->whereHas('appointment', fn($q) => $q->where('customer_id', $customerId))
            ->get();

        $emergencyItems = EmergencyMainItem::with(['item', 'appointment'])
            ->whereHas('appointment', fn($q) => $q->where('customer_id', $customerId))
            ->get();

        $items = collect();

        // Group by item_id
        $groupedInstalled = $installedItems->groupBy('item_id');
        $groupedEmergency = $emergencyItems->groupBy('item_id');

        $allItemIds = $groupedInstalled->keys()->merge($groupedEmergency->keys())->unique();

        foreach ($allItemIds as $itemId) {
            $installRecord = $groupedInstalled->get($itemId)?->sortBy('appointment.appointment_date')->first();
            $latestMaintenance = $groupedEmergency->get($itemId)?->sortByDesc('appointment.appointment_date')->first();

            $items->push([
                'item_id' => $installRecord?->item?->id ?? $latestMaintenance?->item?->id ?? '',
                'item_name' => $installRecord?->item?->name ?? $latestMaintenance?->item?->name ?? '',
                'item_serial' => $installRecord?->serial ?? $latestMaintenance?->serial ?? '',
                'item_image' => $installRecord?->item?->image ?? $latestMaintenance?->item?->image ?? '',
                'installed_date' => $installRecord?->appointment?->appointment_date ?? '',
                'last_maintenance_date' => $latestMaintenance?->appointment?->appointment_date ?? '',
                'last_maintenance_type_name' => $latestMaintenance?->appointment?->maintenance_type ?? ''
            ]);
        }

        return $this->setCode(code: 200)->setData(($items))->setMessage('Success.')->send();
    }

    // attach items with parts to periodic or emergency  appointment
    public function attachItemsWithPartsToAppointment(array $data): mixed
    {
        return DB::transaction(function () use ($data) {
            $authUser = Auth::user();
            $attachedItems = [];

            foreach ($data['items'] as $itemData) {
                $appointment = Appointment::findOrFail($itemData['appointment_id']);

                // Authorization check
                if (
                    $appointment->technician_id !== $authUser->id
                    && !$authUser->hasPermissionTo('update emergency_items')
                    && !$authUser->hasPermissionTo('update periodic_items')
                ) {
                    return $this->setCode(401)
                        ->setData([])
                        ->setMessage('You are not authorized to update this appointment.')
                        ->send();
                }

                // Fetch item model
                $itemModel = Item::findOrFail($itemData['item_id']);

                // Merge item details
                $itemData = array_merge([
                    'serial' => $itemModel->serial ?? null,
                    'name' => $itemModel->name ?? null,
                    'description' => $itemModel->description ?? null,
                    'code' => $itemModel->code ?? null,
                    'image' => $itemModel->image ?? null,
                    'price' => $itemModel->price ?? 0,
                ], $itemData);

                // Common pricing
                $quantity = $itemData['quantity'] ?? 1;
                $price = $itemData['price'];
                $subTotal = $quantity * $price;
                $discount = $itemData['discount'] ?? 0;
                $discountType = $itemData['discount_type'] ?? 'fixed';

                $discountValue = $discountType === 'percentage'
                    ? ($discount / 100) * $subTotal
                    : $discount;

                $totalPrice = $subTotal - $discountValue;

                $itemData['sub_total_price'] = $subTotal;
                $itemData['discount_value'] = $discountValue;
                $itemData['total_price'] = $totalPrice;

                // -- Emergency Logic --
                if ($itemData['type'] === 'emergency') {
                    if ($appointment->type !== 'emergency') {
                        return $this->setCode(404)
                            ->setData([])
                            ->setMessage('Invalid operation: Appointment type must be emergency.')
                            ->send();
                    }

                    $emergencyItem = EmergencyMainItem::create($itemData);

                    // Attach parts if provided
                    if (!empty($itemData['parts'])) {
                        foreach ($itemData['parts'] as $part) {
                            $partModel = Part::findOrFail($part['part_id']);
                            $part = array_merge([
                                'serial' => $partModel->serial ?? null,
                                'name' => $partModel->name ?? null,
                                'description' => $partModel->description ?? null,
                                'code' => $partModel->code ?? null,
                                'image' => $partModel->image ?? null,
                                'price' => $partModel->price ?? 0,
                            ], $part);

                            $q = $part['quantity'] ?? 1;
                            $p = $part['price'];
                            $s = $q * $p;
                            $d = $part['discount'] ?? 0;
                            $dT = $part['discount_type'] ?? 'fixed';
                            $dV = $dT === 'percentage' ? ($d / 100) * $s : $d;
                            $t = $s - $dV;

                            $part['sub_total_price'] = $s;
                            $part['discount_value'] = $dV;
                            $part['total_price'] = $t;
                            $part['appointment_id'] = $emergencyItem->appointment_id;
                            $part['emergency_main_item_id'] = $emergencyItem->id;

                            EmergencyMainItemPart::create($part);
                        }
                    }

                    $attachedItems[] = $emergencyItem->load('parts');

                    // -- Periodic Logic --
                } elseif ($itemData['type'] === 'periodic') {
                    if ($appointment->type !== 'periodic') {
                        return $this->setCode(404)
                            ->setData([])
                            ->setMessage('Invalid operation: Appointment type must be periodic.')
                            ->send();
                    }

                    $periodicItem = PeriodicMainItem::create($itemData);

                    // You can add logic to attach periodic parts if needed

                    $attachedItems[] = $periodicItem;
                }
            }

            return $this->setCode(200)
                ->setData($attachedItems)
                ->setMessage('Items attached successfully.')
                ->send();
        });
    }


    public function deleteEmergencyItemWithParts(int $itemId, string $type): mixed
    {
        $authUser = Auth::user();

        if ($type === 'emergency') {
            $item = EmergencyMainItem::findOrFail($itemId);
            $appointment = Appointment::findOrFail($item->appointment_id);

            if ($appointment->type !== 'emergency') {
                return $this->setCode(404)
                    ->setData([])
                    ->setMessage('Invalid operation: Appointment type must be emergency.')
                    ->send();
            }

            if (
                $appointment->technician_id !== $authUser->id &&
                !$authUser->hasPermissionTo('delete emergency_items')
            ) {
                return $this->setCode(401)
                    ->setData([])
                    ->setMessage('You are not authorized to delete this emergency item.')
                    ->send();
            }

            $count = EmergencyMainItem::where('appointment_id', $item->appointment_id)->count();
            if ($count <= 1) {
                return $this->setCode(422)
                    ->setData([])
                    ->setMessage('At least one item must remain on the appointment.')
                    ->send();
            }

            DB::transaction(function () use ($item) {
                EmergencyMainItemPart::where('emergency_main_item_id', $item->id)->delete();
                $item->delete();
            });

            return $this->setCode(200)
                ->setData([])
                ->setMessage('Emergency item and its parts deleted successfully.')
                ->send();
        } elseif ($type === 'periodic') {
            $item = PeriodicMainItem::findOrFail($itemId);
            $appointment = Appointment::findOrFail($item->appointment_id);

            if ($appointment->type !== 'periodic') {
                return $this->setCode(404)
                    ->setData([])
                    ->setMessage('Invalid operation: Appointment type must be periodic.')
                    ->send();
            }

            if (
                $appointment->technician_id !== $authUser->id &&
                !$authUser->hasPermissionTo('delete periodic_items')
            ) {
                return $this->setCode(401)
                    ->setData([])
                    ->setMessage('You are not authorized to delete this periodic item.')
                    ->send();
            }

            $count = PeriodicMainItem::where('appointment_id', $item->appointment_id)->count();
            if ($count <= 1) {
                return $this->setCode(422)
                    ->setData([])
                    ->setMessage('At least one item must remain on the appointment.')
                    ->send();
            }

            // Handle parts deletion if you have a PeriodicMainItemPart model
            DB::transaction(function () use ($item) {
                // PeriodicMainItemPart::where('periodic_main_item_id', $item->id)->delete(); // Uncomment if exists
                $item->delete();
            });

            return $this->setCode(200)
                ->setData([])
                ->setMessage('Periodic item deleted successfully.')
                ->send();
        }

        return $this->setCode(400)
            ->setData([])
            ->setMessage('Invalid type provided. Must be "emergency" or "periodic".')
            ->send();
    }


    public function attachPartsToEmergencyItem(array $partsData): mixed
    {
        $authUser = Auth::user();
        $createdParts = [];

        foreach ($partsData as $partInput) {
            // Identify type
            $isEmergency = isset($partInput['emergency_main_item_id']);
            $isPeriodic = isset($partInput['periodic_main_item_id']);

            if (!$isEmergency && !$isPeriodic) {
                continue; // skip invalid entry
            }

            $item = $isEmergency
                ? EmergencyMainItem::findOrFail($partInput['emergency_main_item_id'])
                : PeriodicMainItem::findOrFail($partInput['periodic_main_item_id']);

            $appointment = Appointment::findOrFail($item->appointment_id);

            // Validate type match
            if (($isEmergency && $appointment->type !== 'emergency') ||
                ($isPeriodic && $appointment->type !== 'periodic')
            ) {
                return $this->setCode(404)
                    ->setData([])
                    ->setMessage('Invalid operation: Appointment type mismatch.')
                    ->send();
            }

            // Authorization
            if (
                $appointment->technician_id !== $authUser->id &&
                !$authUser->hasPermissionTo('delete emergency_items') &&
                !$authUser->hasPermissionTo('delete periodic_items')
            ) {
                return $this->setCode(401)
                    ->setData([])
                    ->setMessage('You are not authorized to update this appointment item parts.')
                    ->send();
            }

            // Delete old parts
            if ($isEmergency) {
                EmergencyMainItemPart::where('emergency_main_item_id', $item->id)->delete();
            } else {
                PeriodicMainItemPart::where('periodic_main_item_id', $item->id)->delete();
            }
        }

        // Now attach new parts
        foreach ($partsData as $partInput) {
            $isEmergency = isset($partInput['emergency_main_item_id']);
            $isPeriodic = isset($partInput['periodic_main_item_id']);

            if (!isset($partInput['part_id'], $partInput['appointment_id']) || (!$isEmergency && !$isPeriodic)) {
                continue;
            }

            $partModel = Part::findOrFail($partInput['part_id']);

            $part = [
                'serial' => $partModel->serial,
                'name' => $partModel->name,
                'description' => $partModel->description,
                'code' => $partModel->code,
                'image' => $partModel->image,
                'price' => $partModel->price,
                'quantity' => $partInput['part_quantity'] ?? 1,
                'discount' => $partInput['discount'] ?? 0,
                'discount_type' => $partInput['discount_type'] ?? 'fixed',
                'appointment_id' => $partInput['appointment_id'],
                'part_id' => $partInput['part_id'],
            ];

            $subTotal = $part['quantity'] * $part['price'];
            $discountValue = $part['discount_type'] === 'percentage'
                ? ($part['discount'] / 100) * $subTotal
                : $part['discount'];

            $part['sub_total_price'] = $subTotal;
            $part['discount_value'] = $discountValue;
            $part['total_price'] = $subTotal - $discountValue;

            if ($isEmergency) {
                $part['emergency_main_item_id'] = $partInput['emergency_main_item_id'];
                $createdParts[] = EmergencyMainItemPart::create($part);
            } else {
                $part['periodic_main_item_id'] = $partInput['periodic_main_item_id'];
                $createdParts[] = PeriodicMainItemPart::create($part);
            }
        }

        return $this->setCode(200)
            ->setData($createdParts)
            ->setMessage('Parts attached successfully.')
            ->send();
    }
}
