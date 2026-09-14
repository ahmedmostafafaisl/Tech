<?php

namespace App\Repositories\User;

use App\Models\Item;
use App\Models\Part;
use App\Models\Category;
use App\Models\UserStock;
use App\Models\Warehouse;
use App\Models\UserStockItem;
use App\Models\UserStockPart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Http\Resources\User\SingleUserStockResource;
use App\Repositories\Interfaces\UserStockRepositoryInterface;

class UserStockRepository implements UserStockRepositoryInterface
{
    public function getUserStock($userId)
    {
        $userStock = UserStock::with(['items', 'parts'])->where('user_id', $userId)->first();
        return new SingleUserStockResource($userStock);
    }

    public function createOrUpdateUserStock(int $userId, array $items, array $parts)
    {
        $userStock = UserStock::updateOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId] // Ensure it exists
        );

        $addedItems = [];
        foreach ($items as $item) {
            $userStockItem = UserStockItem::updateOrCreate(
                [
                    'user_stock_id' => $userStock->id,
                    'item_id' => $item['item_id']
                ],
                ['quantity' => DB::raw("quantity + {$item['quantity']}")]
            );
            $addedItems[] = $userStockItem;
        }

        $addedParts = [];
        // foreach ($parts as $part) {
        //     $userStockPart = UserStockPart::updateOrCreate(
        //         [
        //             'user_stock_id' => $userStock->id,
        //             'part_id' => $part['part_id']
        //         ],
        //         ['quantity' => DB::raw("quantity + {$part['quantity']}")]
        //     );
        //     $addedParts[] = $userStockPart;
        // }

        return $userStock;
    }

    public function delete($id)
    {
        return UserStock::destroy($id);
    }

    public function addItems(int $userId, array $items)
    {
        $userStock = UserStock::firstOrCreate(['user_id' => $userId]);

        $addedItems = [];

        // foreach ($items as $item) {
        //     $userStockItem = UserStockItem::updateOrCreate(
        //         [
        //             'user_stock_id' => $userStock->id,
        //             'item_id' => $item['item_id']
        //         ],
        //         ['quantity' => DB::raw("quantity + {$item['quantity']}")]
        //     );
        //     $addedItems[] = $userStockItem;
        // }

        return $userStock;
    }

    public function addParts(int $userId, array $parts)
    {
        $userStock = UserStock::firstOrCreate(['user_id' => $userId]);

        $addedParts = [];

        // foreach ($parts as $part) {
        //     $userStockPart = UserStockPart::updateOrCreate(
        //         [
        //             'user_stock_id' => $userStock->id,
        //             'part_id' => $part['part_id']
        //         ],
        //         ['quantity' => DB::raw("quantity + {$part['quantity']}")]
        //     );
        //     $addedParts[] = $userStockPart;
        // }

        return $userStock;
    }

    // Sync customer stock
    public function syncTechnicianStock(array $products, int $technicianId, bool $isFirstPage = false): void
    {
        // DB::beginTransaction();

        // try {
        //     $userStock = UserStock::firstOrCreate(['user_id' => $technicianId]);

        //     // ✅ Clear existing data only on first page
        //     if ($isFirstPage) {
        //         // UserStockItem::where('user_stock_id', $userStock->id)->delete();
        //         // UserStockPart::where('user_stock_id', $userStock->id)->delete();
        //     }

        //     if (empty($products)) {
        //         DB::commit();
        //         return;
        //     }

        //     // ✅ Preload categories & warehouses to reduce queries
        //     $categoryIds = collect($products)->pluck('ProductCategoryId')->filter()->unique()->values();
        //     $warehouseIds = collect($products)->pluck('WarehouseId')->filter()->unique()->values();

        //     $categories = Category::whereIn('product_category_id', $categoryIds)->get()->keyBy('product_category_id');
        //     $warehouses = Warehouse::whereIn('rec_id', $warehouseIds)->get()->keyBy('rec_id');

        //     foreach ($products as $product) {
        //         $quantity = (int) ($product['Quantity'] ?? 0);
        //         $itemNumber = strtolower(trim($product['ItemNumber'] ?? '')); // ✅ force lowercase
        //         // ✅ Skip if no item number or quantity = 0
        //         if (!$itemNumber) {
        //             continue;
        //         }

        //         // ✅ Use cached or create new Category
        //         $category = $categories->get($product['ProductCategoryId']);
        //         if (!$category && !empty($product['ProductCategoryId'])) {
        //             $category = Category::create(['product_category_id' => $product['ProductCategoryId']]);
        //             $categories->put($product['ProductCategoryId'], $category);
        //         }

        //         // ✅ Use cached or create new Warehouse
        //         $warehouse = $warehouses->get($product['WarehouseId']);
        //         if (!$warehouse && !empty($product['WarehouseId'])) {
        //             $warehouse = Warehouse::create(['rec_id' => $product['WarehouseId']]);
        //             $warehouses->put($product['WarehouseId'], $warehouse);
        //         }

        //         $commonData = [
        //             'category_id' => $category->id ?? null,
        //             'warehouse_id' => $warehouse->id ?? null,
        //             'name' => $product['Name'] ?? null,
        //             'description' => $product['Description'] ?? null,
        //             'price' => $product['Price'] ?? 0,
        //             'site_id' => $product['SiteId'] ?? null,
        //             'location_id' => $product['LocationId'] ?? null,
        //             'quantity' => $quantity,
        //             'rec_id' => $product['Id'] ?? null,
        //             'item_number' => $itemNumber,
        //         ];

        //         // ✅ Determine Product Type
        //         $productType = strtolower($product['ProductType'] ?? 'product');

        //         if ($productType === 'product' || $productType === '') {
        //             // --- ITEM ---
        //             $item = Item::where('rec_id', $product['Id'])
        //                 ->where('warehouse_id', $warehouse->id ?? null)
        //                 ->where('item_number', $product['ItemNumber'] ?? null)
        //                 ->first();

        //             if ($item) {
        //                 // check for quantity difference
        //                 if ($item->quantity !== $quantity) {
        //                     Log::info("Item quantity difference", [
        //                         'item_number' => $item->item_number,
        //                         'old_quantity' => $item->quantity,
        //                         'new_quantity' => $quantity,
        //                     ]);
        //                 }
        //                 $item->update($commonData);
        //             } else {
        //                 $item = Item::create($commonData);
        //             }

        //             UserStockItem::updateOrCreate(
        //                 [
        //                     'user_stock_id' => $userStock->id,
        //                     'item_id' => $item->id,
        //                 ],
        //                 [
        //                     'quantity' => $quantity,
        //                     'item_number' => $itemNumber,
        //                 ]
        //             );
        //         } elseif ($productType === 'spare part') {
        //             // --- PART ---
        //             $part = Part::where('rec_id', $product['Id'])
        //                 ->where('warehouse_id', $warehouse->id ?? null)
        //                 ->where('item_number', $product['ItemNumber'] ?? null)
        //                 ->first();

        //             if ($part) {
        //                 if ($part->quantity !== $quantity) {
        //                     Log::info("Part quantity difference", [
        //                         'item_number' => $part->item_number,
        //                         'old_quantity' => $part->quantity,
        //                         'new_quantity' => $quantity,
        //                     ]);
        //                 }
        //                 $part->update($commonData + ['dy_id' => $product['$id'] ?? null]);
        //             } else {
        //                 $part = Part::create($commonData + ['dy_id' => $product['$id'] ?? null]);
        //             }

        //             // UserStockPart::updateOrCreate(
        //             //     [
        //             //         'user_stock_id' => $userStock->id,
        //             //         'part_id' => $part->id,
        //             //     ],
        //             //     [
        //             //         'quantity' => $quantity,
        //             //         'item_number' => $part->item_number,
        //             //     ]
        //             // );
        //         }
        //     }

        //     DB::commit();
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     throw new \Exception("Technician sync failed: " . $e->getMessage());
        // }
    }


    // New method to sync technician stock with improved logic
    // public function newSyncTechnicianStock(array $products, int $technicianId, bool $isFirstPage = false): void
    // {
    //     DB::beginTransaction();

    //     try {
    //         $userStock = UserStock::firstOrCreate(['user_id' => $technicianId]);

    //         // ✅ Clear existing data only on first page
    //         if ($isFirstPage) {
    //             // UserStockItem::where('user_stock_id', $userStock->id)->delete();
    //             // UserStockPart::where('user_stock_id', $userStock->id)->delete();
    //         }

    //         if (empty($products)) {
    //             DB::commit();
    //             return;
    //         }

    //         // ✅ Preload categories & warehouses to reduce queries
    //         $categoryIds = collect($products)->pluck('ProductCategoryId')->filter()->unique()->values();
    //         $warehouseIds = collect($products)->pluck('WarehouseId')->filter()->unique()->values();

    //         $categories = Category::whereIn('product_category_id', $categoryIds)->get()->keyBy('product_category_id');
    //         $warehouses = Warehouse::whereIn('rec_id', $warehouseIds)->get()->keyBy('rec_id');

    //         foreach ($products as $product) {
    //             $quantity = (int) ($product['Quantity'] ?? 0);
    //             $itemNumber = strtolower(trim($product['ItemNumber'] ?? '')); // ✅ force lowercase
    //             // ✅ Skip if no item number or quantity = 0
    //             if (!$itemNumber || $quantity <= 0) {
    //                 continue;
    //             }

    //             // ✅ Use cached or create new Category
    //             $category = $categories->get($product['ProductCategoryId']);
    //             if (!$category && !empty($product['ProductCategoryId'])) {
    //                 $category = Category::create(['product_category_id' => $product['ProductCategoryId']]);
    //                 $categories->put($product['ProductCategoryId'], $category);
    //             }

    //             // ✅ Use cached or create new Warehouse
    //             $warehouse = $warehouses->get($product['WarehouseId']);
    //             if (!$warehouse && !empty($product['WarehouseId'])) {
    //                 $warehouse = Warehouse::create(['rec_id' => $product['WarehouseId']]);
    //                 $warehouses->put($product['WarehouseId'], $warehouse);
    //             }

    //             $commonData = [
    //                 'category_id' => $category->id ?? null,
    //                 'warehouse_id' => $warehouse->id ?? null,
    //                 'name' => $product['Name'] ?? null,
    //                 'description' => $product['Description'] ?? null,
    //                 'price' => $product['Price'] ?? 0,
    //                 'site_id' => $product['SiteId'] ?? null,
    //                 'location_id' => $product['LocationId'] ?? null,
    //                 'quantity' => $quantity,
    //                 'rec_id' => $product['Id'] ?? null,
    //                 'item_number' => $itemNumber,
    //             ];

    //             // ✅ Determine Product Type
    //             $productType = strtolower($product['ProductType'] ?? 'product');

    //             if ($productType === 'product' || $productType === '') {
    //                 // --- ITEM ---
    //                 $item = Item::where('rec_id', $product['Id'])
    //                     ->where('warehouse_id', $warehouse->id ?? null)
    //                     ->where('item_number', $product['ItemNumber'] ?? null)
    //                     ->first();

    //                 if ($item) {
    //                     // check for quantity difference
    //                     if ($item->quantity !== $quantity) {
    //                         Log::info("Item quantity difference", [
    //                             'item_number' => $item->item_number,
    //                             'old_quantity' => $item->quantity,
    //                             'new_quantity' => $quantity,
    //                         ]);
    //                     }
    //                     $item->update($commonData);
    //                 } else {
    //                     $item = Item::create($commonData);
    //                 }

    //                 UserStockItem::updateOrCreate(
    //                     [
    //                         'user_stock_id' => $userStock->id,
    //                         'item_id' => $item->id,
    //                     ],
    //                     [
    //                         'quantity' => $quantity,
    //                         'item_number' => $itemNumber,
    //                     ]
    //                 );
    //             } elseif ($productType === 'spare part') {
    //                 // --- PART ---
    //                 $part = Part::where('rec_id', $product['Id'])
    //                     ->where('warehouse_id', $warehouse->id ?? null)
    //                     ->where('item_number', $product['ItemNumber'] ?? null)
    //                     ->first();

    //                 if ($part) {
    //                     if ($part->quantity !== $quantity) {
    //                         Log::info("Part quantity difference", [
    //                             'item_number' => $part->item_number,
    //                             'old_quantity' => $part->quantity,
    //                             'new_quantity' => $quantity,
    //                         ]);
    //                     }
    //                     $part->update($commonData + ['dy_id' => $product['$id'] ?? null]);
    //                 } else {
    //                     $part = Part::create($commonData + ['dy_id' => $product['$id'] ?? null]);
    //                 }

    //                 // UserStockPart::updateOrCreate(
    //                 //     [
    //                 //         'user_stock_id' => $userStock->id,
    //                 //         'part_id' => $part->id,
    //                 //     ],
    //                 //     [
    //                 //         'quantity' => $quantity,
    //                 //         'item_number' => $part->item_number,
    //                 //     ]
    //                 // );
    //             }
    //         }

    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw new \Exception("Technician sync failed: " . $e->getMessage());
    //     }
    // }



    // Update stock quantity for a specific item or part
    public function bulkUpdateStockQuantities(string $type, array $data): array
    {
        $user = Auth::user();

        if (!$user) {
            return ['status' => false, 'message' => 'Unauthenticated user'];
        }

        $stock = $user->stock()->first();

        if (!$stock) {
            return ['status' => false, 'message' => 'No stock found for this user'];
        }

        $updated = [];
        $notFound = [];

        foreach ($data as $row) {
            $itemNumber = $row['item_number'] ?? null;
            $itemId     = $row['item_id'] ?? null;
            $partId     = $row['part_id'] ?? null;
            $quantity   = $row['quantity'] ?? null;

            if (!is_numeric($quantity)) {
                $notFound[] = [
                    'item_number' => $itemNumber,
                    'id'          => $itemId ?? $partId,
                    'message'     => 'Invalid quantity',
                ];
                continue;
            }

            // Determine record based on type and provided identifier
            if ($type === 'item') {
                $query = $stock->items();

                if ($itemId) {
                    $record = $query->where('item_id', $itemId)->first();
                } elseif ($itemNumber) {
                    $record = $query->where('item_number', $itemNumber)->first();
                } else {
                    $notFound[] = [
                        'message' => 'Missing item identifier (item_id or item_number)',
                    ];
                    continue;
                }
            } elseif ($type === 'part') {
                $query = $stock->parts();

                if ($partId) {
                    $record = $query->where('part_id', $partId)->first();
                } elseif ($itemNumber) {
                    $record = $query->where('item_number', $itemNumber)->first();
                } else {
                    $notFound[] = [
                        'message' => 'Missing part identifier (part_id or item_number)',
                    ];
                    continue;
                }
            } else {
                return ['status' => false, 'message' => 'Invalid type (must be item or part)'];
            }

            // Update if found
            if ($record) {
                $record->quantity = (int) $quantity;
                $record->save();

                $updated[] = [
                    'id'          => $record->id,
                    'item_number' => $record->item_number,
                    'quantity'    => $record->quantity,
                    'status'      => 'updated',
                ];
            } else {
                $notFound[] = [
                    'id'          => $itemId ?? $partId,
                    'item_number' => $itemNumber,
                    'message'     => 'Record not found',
                ];
            }
        }

        return [
            'status'    => true,
            'message'   => 'Stock quantities processed successfully',
            'updated'   => $updated,
            'not_found' => $notFound,
        ];
    }


    // public function bulkUpdateStockQuantities(string $type, array $data): array
    // {
    //     $user = Auth::user();

    //     if (!$user) {
    //         return ['status' => false, 'message' => 'Unauthenticated user'];
    //     }

    //     $stock = $user->stock()->first();

    //     if (!$stock) {
    //         return ['status' => false, 'message' => 'No stock found for this user'];
    //     }

    //     $updated = [];
    //     $created = [];
    //     $invalid = [];

    //     foreach ($data as $row) {
    //         $itemNumber = $row['item_number'] ?? null;
    //         $quantity   = $row['quantity'] ?? null;

    //         if (!$itemNumber || !is_numeric($quantity)) {
    //             $invalid[] = [
    //                 'item_number' => $itemNumber,
    //                 'message'     => 'Invalid data provided',
    //             ];
    //             continue;
    //         }

    //         if ($type === 'item') {
    //             $relation = $stock->items();
    //             $modelClass = \App\Models\Item::class;
    //             $foreignKey = 'item_id';
    //         } elseif ($type === 'part') {
    //             $relation = $stock->parts();
    //             $modelClass = \App\Models\Part::class;
    //             $foreignKey = 'part_id';
    //         } else {
    //             return ['status' => false, 'message' => 'Invalid type (must be item or part)'];
    //         }

    //         // Try to find the existing user_stock record
    //         $record = $relation->where('item_number', $itemNumber)->first();

    //         // Find the main item/part by item_number
    //         $mainModel = $modelClass::where('item_number', $itemNumber)->first();

    //         if ($record) {
    //             // ✅ Update existing
    //             $record->update(['quantity' => (int) $quantity]);

    //             $updated[] = [
    //                 'item_number' => $itemNumber,
    //                 'quantity'    => $record->quantity,
    //                 'status'      => 'updated',
    //             ];
    //         } else {
    //             // ✅ Create new record if not found
    //             $newRecord = $relation->create([
    //                 'item_number'   => $itemNumber,
    //                 'quantity'      => (int) $quantity,
    //                 $foreignKey     => $mainModel?->id, // link to first matching item/part if found
    //             ]);

    //             $created[] = [
    //                 'item_number' => $itemNumber,
    //                 'quantity'    => $newRecord->quantity,
    //                 'status'      => 'created',
    //                 'linked_to'   => $mainModel ? $mainModel->id : null,
    //             ];
    //         }
    //     }

    //     return [
    //         'status'   => true,
    //         'message'  => 'Stock quantities processed successfully',
    //         'updated'  => $updated,
    //         'created'  => $created,
    //         'invalid'  => $invalid,
    //     ];
    // }


    public function syncMultipleItems(array $itemNumbers, int $technicianId): array
    {
        $results = [];

        foreach ($itemNumbers as $itemNumber) {
            try {
                // Dispatch artisan command asynchronously
                Artisan::queue('sync:technician-stock', [
                    'tech_id' => $technicianId,
                    '--itemNumber' => $itemNumber,
                ]);

                $results[] = [
                    'item_number' => $itemNumber,
                    'status' => 'queued',
                ];
            } catch (\Throwable $e) {
                // Log and continue loop
                Log::error('Failed to dispatch technician stock sync command', [
                    'tech_id'    => $technicianId,
                    'itemNumber' => $itemNumber,
                    'error'      => $e->getMessage(),
                ]);

                $results[] = [
                    'item_number' => $itemNumber,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                continue;
            }
        }

        return $results;
    }
}
