<?php



namespace App\Services;

use App\Models\Item;
use App\Models\Part;
use App\Models\Category;
use App\Models\UserStock;
use App\Models\Appointment;
use App\Models\WarehouseTransfer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\WarehouseRequestedItem;
use App\Models\WarehouseRequestedPart;
use App\Models\WarehouseTransferredItem;
use App\Models\WarehouseTransferredPart;
use Illuminate\Validation\ValidationException;

class WarehouseTransferFillService
{

    public function validateItemsAndParts(array $data, string $type, int $warehouseId, ?UserStock $userStock = null): void
    {
        foreach ($data['requested_items'] ?? [] as $item) {
            // dd($item);
            if ($type === 'warehouse_to_tech') {
                $warehouseItem = Item::where('warehouse_id', $warehouseId)
                    ->where('id', $item['item_id'])->first();
                if (!$warehouseItem || $warehouseItem->quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'requested_items' => ["Item ID {$item['item_id']} is not available or insufficient in warehouse."],
                    ]);
                }
            } else {
                $stockItem = $userStock->items()->where('item_id', $item['item_id'])->first();
                if (!$stockItem || $stockItem->quantity < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'requested_items' => ["Item ID {$item['item_id']} is not available or insufficient in user stock."],
                    ]);
                }
            }
        }
        foreach ($data['requested_parts'] ?? [] as $part) {
            if ($type === 'warehouse_to_tech') {
                $warehousePart = Part::where('warehouse_id', $warehouseId)
                    ->where('id', $part['part_id'])->first();

                if (!$warehousePart || $warehousePart->quantity < $part['quantity']) {
                    throw ValidationException::withMessages([
                        'requested_parts' => ["Part ID {$part['part_id']} is not available or insufficient in warehouse."],
                    ]);
                }
            } else {
                $stockPart = $userStock->parts()->where('part_id', $part['part_id'])->first();

                if (!$stockPart || $stockPart->quantity < $part['quantity']) {
                    throw ValidationException::withMessages([
                        'requested_parts' => ["Part ID {$part['part_id']} is not available or insufficient in user stock."],
                    ]);
                }
            }
        }
    }



    public function storeTransfer(array $data): WarehouseTransfer
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $techId = auth()->id();
            $warehouseId = $data['warehouse_id'];
            $type = $data['type'] ?? 'warehouse_to_tech';
            $userStock = null;
            $status = 'pending';

            if ($type === 'tech_to_warehouse') {
                if ($user->type == 'tech') {
                    $status = 'processing';
                }
                $userStock = UserStock::where('user_id', $techId)->first();
                if (!$userStock) {
                    throw ValidationException::withMessages(['user_stock' => ['No stock found for the tech.']]);
                }
            }

            $this->validateItemsAndParts($data, $type, $warehouseId, $userStock);

            $transfer = WarehouseTransfer::create([
                'warehouse_id' => $warehouseId,
                'tech_id' => $techId,
                'date' => now(),
                'status' => $status,
                'reference_id' => $data['reference_id'] ?? null,
                'type' => $type,
            ]);

            foreach ($data['requested_items'] ?? [] as $item) {
                WarehouseRequestedItem::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ]);
            }


            foreach ($data['requested_parts'] ?? [] as $part) {
                WarehouseRequestedPart::create([
                    'warehouse_transfer_id' => $transfer->id,
                    'part_id' => $part['part_id'],
                    'quantity' => $part['quantity'],
                ]);
            }

            return $transfer;
        });
    }


    public function updateTransfer($transfer, array $data): WarehouseTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            $techId = auth()->id();
            $user = auth()->user();
            $warehouseId = $transfer->warehouse_id;
            $type =  $transfer->type;
            $status = $data['status'];
            $userStock = UserStock::where('user_id', $techId)->first();
            if (!$userStock) {
                throw ValidationException::withMessages(['user_stock' => ['No stock found for the tech.']]);
            }

            if ($user->type == 'tech' &&  $transfer->tech_id !=  $techId) {
                throw ValidationException::withMessages([
                    'user' => ['You do not have permission to update this transfer.'],
                ]);
            }
            // tech_to_warehouse
            // check if  user not has permission
            if ($type === 'tech_to_warehouse') {
                if ($user->type !== 'tech' || !$user->can('update transfers')) {
                    throw ValidationException::withMessages([
                        'user' => ['You do not have permission to update this transfer.'],
                    ]);
                }
                // check if  tech that he cant received or rejected transfer
                if ($user->type === 'tech' || $status === 'received' || $status === 'rejected') {
                    throw ValidationException::withMessages([
                        'user' => ['You do not have permission to update this transfer.'],
                    ]);
                }
            }
            // warehouse_to_tech
            // check if  user not has permission
            if ($type === 'warehouse_to_tech') {
                if ($user->type !== 'tech' || !$user->can('update transfers') && $transfer->status != 'pending') {
                    throw ValidationException::withMessages([
                        'user' => ['You do not have permission to update this transfer.'],
                    ]);
                }

                // check if  tech not has permission to update the transfer
                if (!($user->type === 'tech' && $transfer->status === 'processing'
                    && in_array($status, ['processing', 'received', 'rejected']))) {
                    throw ValidationException::withMessages([
                        'user' => ['You do not have permission to update this transfer.'],
                    ]);
                }
            }


            $this->validateItemsAndParts($data, $type, $warehouseId, $userStock);

            $transfer->update([
                'status' => $data['status'],
            ]);

            // update items and parts
            // items
            $transferredItems = isset($data['transferred_items'])
                ? $data['transferred_items']
                : ($transfer->transferredItems()->exists()
                    ? $transfer->transferredItems->toArray()
                    : $transfer->requestedItems->toArray());
            // parts
            $transferredParts = isset($data['transferred_parts'])
                ? $data['transferred_parts']
                : ($transfer->transferredParts()->exists()
                    ? $transfer->transferredParts->toArray()
                    : $transfer->requestedParts->toArray());


            if ($transferredItems) {
                // Clear old records (optional: keep if updating instead)
                WarehouseTransferredItem::where('warehouse_transfer_id', $transfer->id)->delete();

                foreach ($transferredItems as $item) {
                    WarehouseTransferredItem::create([
                        'warehouse_transfer_id' => $transfer->id,
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            if ($transferredParts) {
                WarehouseTransferredPart::where('warehouse_transfer_id', $transfer->id)->delete();
                foreach ($transferredParts as $part) {
                    WarehouseTransferredPart::create([
                        'warehouse_transfer_id' => $transfer->id,
                        'part_id' => $part['part_id'],
                        'quantity' => $part['quantity'],
                    ]);
                }
            }

            if ($status === 'received' && $transfer->status !== 'received') {
                $this->syncTransferRelations($transfer, $data,  $transferredItems, $transferredParts);
            }

            return $transfer;
        });
    }


    protected function syncTransferRelations(WarehouseTransfer $transfer, array $data,  $transferredItems, $transferredParts): void
    {
        $warehouseId = $transfer->warehouse_id;
        $categoryId = Category::first()->id;
        $techId = $transfer->tech_id;
        $type = $transfer->type;
        //user stock
        $userStock = UserStock::firstOrCreate(['user_id' => $techId]);
        // Sync transferred items
        foreach ($transferredItems ?? [] as $item) {
            if ($type === 'warehouse_to_tech') {
                // ➖ Minus from warehouse
                $warehouseItem =  Item::where('id', $item['item_id'])
                    ->first();
                if ($warehouseItem) {
                    $warehouseItem->decrement('quantity', $item['quantity']);
                }
                // ➕ Add to user stock
                $stockItem = $userStock->items()->where('item_id', $item['item_id'])->first();
                if ($stockItem) {
                    $stockItem->increment('quantity', $item['quantity']);
                } else {
                    $userStock->items()->create([
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'user_stock_id' => $userStock->id,
                    ]);
                }
            } else {
                // ➖ Minus from user stock
                $stockItem = $userStock->items()->where('item_id', $item['item_id'])->first();
                if ($stockItem) {
                    $stockItem->decrement('quantity', $item['quantity']);
                    if ($stockItem->quantity <= 0) $stockItem->delete();
                }
                // ➕ Add to warehouse
                $warehouseItem = Item::where('id', $item['item_id'])
                    ->first();
                if ($warehouseItem) {
                    $warehouseItem->increment('quantity', $item['quantity']);
                } else {
                    Item::create([
                        'id' => $item['item_id'],
                        'warehouse_id' => $warehouseId,
                        'category_id' =>    $categoryId,
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
        }
        // Sync transferred parts
        foreach ($transferredParts ?? [] as $part) {
            if ($type === 'warehouse_to_tech') {
                // ➖ Minus from warehouse
                $warehousePart = Part::where('id', $part['part_id'])
                    ->first();
                if ($warehousePart) {
                    $warehousePart->decrement('quantity', $part['quantity']);
                }
                // ➕ Add to user stock
                $stockPart = $userStock->parts()->where('part_id', $part['part_id'])->first();
                if ($stockPart) {
                    $stockPart->increment('quantity', $part['quantity']);
                } else {
                    $userStock->parts()->create([
                        'part_id' => $part['part_id'],
                        'quantity' => $part['quantity'],
                        'user_stock_id' => $userStock->id,
                    ]);
                }
            } else {
                // ➖ Minus from user stock
                $stockPart = $userStock->parts()->where('part_id', $part['part_id'])->first();
                if ($stockPart) {
                    $stockPart->decrement('quantity', $part['quantity']);
                    if ($stockPart->quantity <= 0) $stockPart->delete();
                }
                // ➕ Add to warehouse
                $warehousePart = Part::where('id', $part['part_id'])
                    ->first();
                if ($warehousePart) {
                    $warehousePart->increment('quantity', $part['quantity']);
                } else {
                    Part::create([
                        'id' => $part['part_id'],
                        'warehouse_id' => $warehouseId,
                        'category_id' =>    $categoryId,
                        'quantity' => $part['quantity'],
                    ]);
                }
            }
        }
    }

    public function deductAppointmentLinesFromWarehouse(Appointment $appointment)
    {
        $technician = $appointment->technician; // appointment belongsTo tech
        $warehouse  = $technician->warehouse;   // tech hasOne warehouse

        if (!$warehouse) {
            throw new \Exception("Technician {$technician->id} does not have a warehouse assigned.");
        }

        $lines = $appointment->lines; // appointment hasMany items (lines)

        if ($lines->isEmpty()) {
            throw new \Exception("Appointment {$appointment->id} has no lines to deduct.");
        }

        foreach ($lines as $line) {
            // Skip if no type
            if (!$line->type) {
                Log::warning("Line {$line->id} has no type, skipping deduction.");
                continue;
            }

            // Identify stock model based on type
            if ($line->type === 'item') {
                $stock = $warehouse->items()->where('item_number', $line->item_number)->first();
            } elseif ($line->type === 'part') {
                $stock = $warehouse->parts()->where('item_number', $line->item_number)->first();
            } else {
                Log::warning("Unsupported line type [{$line->type}] on line {$line->id}");
                continue;
            }

            if (!$stock) {
                Log::error("Stock not found in warehouse {$warehouse->id} for item_number {$line->item_number}");
                continue;
            }

            // Deduct quantity
            if ($stock->quantity < $line->quantity) {
                Log::error("Insufficient stock in warehouse {$warehouse->id} for item_number {$line->item_number}");
                continue;
            }

            $stock->decrement('quantity', $line->quantity);
        }

        return true;
    }
}
