<?php



namespace App\Repositories\Warehouse;

use App\Models\Item;
use App\Models\Part;
use App\Models\Category;
use App\Models\Warehouse;
use App\Helper\ApiResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\WarehouseInterface;

class WarehouseRepository implements WarehouseInterface
{
    use ApiResponseHelper;

    public function all()
    {
        return Warehouse::all();
    }

    public function find($id)
    {
        return Warehouse::findOrFail($id);
    }

    public function create(array $data)
    {
        return Warehouse::create($data);
    }

    public function update($id, array $data)
    {
        $warehouse = $this->find($id);
        $warehouse->update($data);
        return $warehouse;
    }

    public function delete($id)
    {
        return Warehouse::destroy($id);
    }

    // create or update warehouse data from the dy365
    public function syncWarehouses(array $warehouses): void
    {
        DB::beginTransaction();
        try {
            foreach ($warehouses as $warehouseData) {
                Warehouse::updateOrCreate(
                    ['rec_id' => $warehouseData['RecId']],
                    [
                        'invent_location_id' => $warehouseData['InventLocationId'],
                        'name' => $warehouseData['Name'],
                        'type' => $warehouseData['Type'] ?? null,

                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Failed to sync warehouses: " . $e->getMessage());
        }
    }

    // get all Inventoryuse Illuminate\Pagination\LengthAwarePaginator;


    public function getInventory($perPage, $page, $type = null)
    {
        $authUser = Auth::user();
        if (!$authUser->can('view tasks')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to view this items.')
                ->send();
        }

        $items = collect();
        $parts = collect();

        // Conditionally load items
        if ($type === null || $type === 'items') {
            $items = Item::with(['warehouse', 'category'])->get()->map(function ($item) {
                return [
                    'id'        => $item->id,
                    'name'      => $item->name,
                    'price'     => $item->price,
                    'quantity'  => $item->quantity,
                    'serial'    => $item->serial,
                    'code'      => $item->code,
                    'image'     => $item->image,
                    'warehouse' => optional($item->warehouse)->name,
                    'category'  => optional($item->category)->name,
                    'type'      => 'item',
                ];
            });
        }

        // Conditionally load parts
        if ($type === null || $type === 'parts') {
            $parts = Part::with(['warehouse', 'category'])->get()->map(function ($part) {
                return [
                    'id'        => $part->id,
                    'name'      => $part->name,
                    'price'     => $part->price,
                    'quantity'  => $part->quantity,
                    'serial'    => $part->serial,
                    'code'      => $part->code,
                    'image'     => $part->image,
                    'warehouse' => optional($part->warehouse)->name,
                    'category'  => optional($part->category)->name,
                    'type'      => 'part',
                ];
            });
        }

        $merged = $parts->concat($items)->sortBy('name')->values();

        $offset = ($page - 1) * $perPage;
        $paginated = new LengthAwarePaginator(
            $merged->slice($offset, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $this->setCode(200)
            ->setData([
                'inventory' => $paginated->items(),
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'total_pages' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total_items' => $paginated->total(),
                ],
            ])
            ->setMessage('success')
            ->send();
    }



    // Sync Warehouse stock
    public function syncWarehouseStock(array $products): void
    {
        DB::beginTransaction();

        try {
            // ✅ Filter out invalid or empty products
            $products = collect($products)->filter(function ($p) {
                return !empty($p['ItemNumber']) && ($p['Quantity'] ?? 0) > 0;
            });

            if ($products->isEmpty()) {
                DB::commit();
                return;
            }

            // ✅ Preload all categories & warehouses once
            $categoryIds  = $products->pluck('ProductCategoryId')->filter()->unique();
            $warehouseIds = $products->pluck('WarehouseId')->filter()->unique();

            $categories = Category::whereIn('product_category_id', $categoryIds)
                ->get()
                ->keyBy('product_category_id');

            $warehouses = Warehouse::whereIn('rec_id', $warehouseIds)
                ->get()
                ->keyBy('rec_id');

            foreach ($products as $product) {
                $itemNumber = strtolower(trim($product['ItemNumber'] ?? ''));

                if (!$itemNumber) {
                    continue;
                }

                $categoryId  = $product['ProductCategoryId'] ?? null;
                $warehouseId = $product['WarehouseId'] ?? null;

                // ✅ Use cached category or create it if missing
                $category = $categories->get($categoryId);
                if (!$category && $categoryId) {
                    $category = Category::create(['product_category_id' => $categoryId]);
                    $categories->put($categoryId, $category);
                }

                // ✅ Use cached warehouse or create it if missing
                $warehouse = $warehouses->get($warehouseId);
                if (!$warehouse && $warehouseId) {
                    $warehouse = Warehouse::create(['rec_id' => $warehouseId]);
                    $warehouses->put($warehouseId, $warehouse);
                }

                $commonData = [
                    'category_id'  => $category->id ?? null,
                    'warehouse_id' => $warehouse->id ?? null,
                    'name'         => $product['Name'] ?? null,
                    'description'  => $product['Description'] ?? null,
                    'price'        => $product['Price'] ?? 0,
                    'site_id'      => $product['SiteId'] ?? null,
                    'location_id'  => $product['LocationId'] ?? null,
                    'quantity'     => $product['Quantity'] ?? 0,
                    'rec_id'       => $product['Id'] ?? null,
                    'item_number'  => $product['ItemNumber'] ?? null,
                ];

                $productType = strtolower($product['ProductType'] ?? '');

                // ✅ Choose correct model (item or part)
                if ($productType === 'product' || $productType === '') {
                    Item::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id ?? null,
                            'rec_id'       => $product['Id'] ?? null,
                        ],
                        $commonData
                    );
                } elseif ($productType === 'spare part') {
                    Part::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id ?? null,
                            'rec_id'       => $product['Id'] ?? null,
                        ],
                        $commonData
                    );
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Warehouse sync failed', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new \Exception("Warehouse sync failed: " . $e->getMessage());
        }
    }

    // public function syncWarehouseStock(array $products): void
    // {
    //     DB::beginTransaction();
    //     try {
    //         foreach ($products as $product) {
    //             $quantity = $product['Quantity'] ?? 0;

    //             // ✅ Skip if quantity is 0 or less
    //             if ($quantity <= 0) {
    //                 continue;
    //             }

    //             $category = Category::firstOrCreate(
    //                 ['product_category_id' => $product['ProductCategoryId']]
    //             );

    //             $warehouse = Warehouse::firstOrCreate(
    //                 ['rec_id' => $product['WarehouseId']]
    //             );

    //             $commonData = [
    //                 'category_id'  => $category->id,
    //                 'warehouse_id' => $warehouse->id,
    //                 'name'         => $product['Name'],
    //                 'description'  => $product['Description'],
    //                 'price'        => $product['Price'] ?? 0,
    //                 'site_id'      => $product['SiteId'] ?? null,
    //                 'location_id'  => $product['LocationId'] ?? null,
    //                 'quantity'     => $product['Quantity'],
    //                 'rec_id'       => $product['Id'] ?? null,
    //                 'item_number'  => $product['ItemNumber'],
    //             ];

    //             if (($product['ProductType'] ?? '') === 'Product' || ($product['ProductType'] ?? '') === '') {
    //                 // ✅ Update or create Item with item_number + rec_id + warehouse_id
    //                 Item::updateOrCreate(
    //                     [
    //                         // 'item_number'  => $product['ItemNumber'],
    //                         'rec_id'       => $product['Id'] ?? null,
    //                         'warehouse_id' => $warehouse->id,
    //                     ],
    //                     $commonData
    //                 );
    //             } elseif ($product['ProductType'] === 'Spare Part') {
    //                 // ✅ Update or create Part with item_number + rec_id + warehouse_id
    //                 Part::updateOrCreate(
    //                     [
    //                         // 'item_number'  => $product['ItemNumber'],
    //                         'rec_id'       => $product['Id'] ?? null,
    //                         'warehouse_id' => $warehouse->id,
    //                     ],
    //                     $commonData
    //                 );
    //             }
    //         }

    //         DB::commit();
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw new \Exception("Warehouse sync failed: " . $e->getMessage());
    //     }
    // }
}
