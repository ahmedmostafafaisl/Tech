<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    WarehouseTransfer,
    WarehouseRequestedItem,
    WarehouseTransferredItem,
    WarehouseRequestedPart,
    WarehouseTransferredPart,
    Warehouse,
    User,
    Item,
    Part
};
use Carbon\Carbon;

class WarehouseTransferSeeder extends Seeder
{
    public function run(): void
    {
        // Get available warehouses, items, and parts
        $warehouses = Warehouse::all();
        $items = Item::all();
        $parts = Part::all();

        if ($warehouses->isEmpty() || $items->isEmpty() || $parts->isEmpty()) {
            $this->command->warn('Ensure warehouses, items, and parts exist before seeding.');
            return;
        }

        // Get all technicians
        $technicians = User::where('type', 'tech')->get();

        foreach ($technicians as $tech) {
            $warehouse = $warehouses->random();

            // --- Transfer 1: warehouse to tech ---
            $transfer1 = WarehouseTransfer::create([
                'warehouse_id' => $warehouse->id,
                'tech_id' => $tech->id,
                'date' => Carbon::now(),
                'status' => 'pending',
                'reference_id' => 'REF-W2T-' . strtoupper(uniqid()),
                'type' => 'warehouse_to_tech',
            ]);

            $this->attachItemsAndParts($transfer1, $items, $parts);

            // --- Transfer 2: tech to warehouse ---
            $transfer2 = WarehouseTransfer::create([
                'warehouse_id' => $warehouse->id,
                'tech_id' => $tech->id,
                'date' => Carbon::now(),
                'status' => 'pending',
                'reference_id' => 'REF-T2W-' . strtoupper(uniqid()),
                'type' => 'tech_to_warehouse',
            ]);

            $this->attachItemsAndParts($transfer2, $items, $parts);

            $this->command->info("Created 2 transfers for tech ID {$tech->id}");
        }
    }

    private function attachItemsAndParts(WarehouseTransfer $transfer, $items, $parts): void
    {
        $selectedItems = $items->random(min(3, $items->count()));
        $selectedParts = $parts->random(min(3, $parts->count()));

        // Track requested item quantities
        $requestedItems = [];
        foreach ($selectedItems as $item) {
            $requestedQty = rand(3, 10);
            WarehouseRequestedItem::create([
                'warehouse_transfer_id' => $transfer->id,
                'item_id' => $item->id,
                'quantity' => $requestedQty,
            ]);
            $requestedItems[] = [
                'id' => $item->id,
                'quantity' => $requestedQty,
            ];
        }

        foreach ($requestedItems as $req) {
            WarehouseTransferredItem::create([
                'warehouse_transfer_id' => $transfer->id,
                'item_id' => $req['id'],
                'quantity' => rand(1, $req['quantity']), // Ensure transferred <= requested
            ]);
        }

        // Track requested part quantities
        $requestedParts = [];
        foreach ($selectedParts as $part) {
            $requestedQty = rand(3, 10);
            WarehouseRequestedPart::create([
                'warehouse_transfer_id' => $transfer->id,
                'part_id' => $part->id,
                'quantity' => $requestedQty,
            ]);
            $requestedParts[] = [
                'id' => $part->id,
                'quantity' => $requestedQty,
            ];
        }

        foreach ($requestedParts as $req) {
            WarehouseTransferredPart::create([
                'warehouse_transfer_id' => $transfer->id,
                'part_id' => $req['id'],
                'quantity' => rand(1, $req['quantity']), // Ensure transferred <= requested
            ]);
        }
    }
}
