<?php

namespace App\Http\Controllers\Api\User;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\DY365\DyService;
use App\Http\Controllers\Controller;
use App\Jobs\SyncTechnicianStockJob;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\UserStockResource;
use App\Http\Requests\User\StoreUserStockRequest;
use App\Http\Requests\User\UpdateUserStockRequest;
use App\Repositories\Interfaces\UserStockRepositoryInterface;
use App\Exports\UserStockExport;
use Maatwebsite\Excel\Facades\Excel;

class UserStockController extends Controller
{
    protected $userStockRepository;
    protected DyService $dyService;

    public function __construct(DyService $dynamicsService, UserStockRepositoryInterface $userStockRepository)
    {
        $this->userStockRepository = $userStockRepository;
        $this->dyService = $dynamicsService;
    }

    public function show(int $userId)
    {
        return $stock = $this->userStockRepository->getUserStock($userId);
    }

    public function storeOrUpdate(StoreUserStockRequest $request)
    {
        $data = $request->validated();

        $result = $this->userStockRepository->createOrUpdateUserStock(
            $data['user_id'],
            $data['items'] ?? [],
            $data['parts'] ?? []
        );

        return response()->json([
            'message' => 'User stock updated successfully',
            'data' => new UserStockResource($result)
        ]);
    }

    public function destroy(int $id)
    {
        $deleted = $this->userStockRepository->delete($id);

        return response()->json([
            'message' => $deleted ? 'User stock deleted successfully' : 'User stock not found'
        ], $deleted ? 200 : 404);
    }

    public function addItems(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        $result = $this->userStockRepository->addItems($data['user_id'], $data['items']);

        return response()->json([
            'message' => 'Items added successfully',
            'data' => new UserStockResource($result)
        ]);
    }

    public function addParts(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'parts' => 'required|array',
            'parts.*.part_id' => 'required|exists:parts,id',
            'parts.*.quantity' => 'required|integer|min:1'
        ]);

        $result = $this->userStockRepository->addParts($data['user_id'], $data['parts']);

        return response()->json([
            'message' => 'Parts added successfully',
            'data' => new UserStockResource($result)
        ]);
    }


    public function syncTechnicianStock()
    {
        try {
            $users = User::where('type', 'tech')
                ->orderBy('id')
                ->get();
            if ($users->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'No technicians found.'], 404);
            }

            return    $summary = [];
            $pageSize = 300; // You can adjust this

            foreach ($users as $technician) {
                if (!$technician->warehouse_id) {
                    $summary[] = [
                        'technician_id' => $technician->id,
                        'status' => 'skipped',
                        'message' => 'Missing warehouse_id'
                    ];
                    continue;
                }

                $page = 1;
                $totalSynced = 0;

                do {
                    $payload = [
                        'warehouseId' => $technician->warehouse_id,
                        'currentPage' => $page,
                        'pageSize' => $pageSize,
                    ];

                    $response = $this->dyService->getWarehouseStock($payload);
                    $pagination = $response['Data'] ?? [];
                    $products = $pagination['Products'] ?? [];

                    if (empty($products)) {
                        if ($page === 1) {
                            $summary[] = [
                                'technician_id' => $technician->id,
                                'status' => 'failed',
                                'message' => 'No products found'
                            ];
                        }
                        break;
                    }

                    $this->userStockRepository->syncTechnicianStock($products, $technician->id);
                    $totalSynced += count($products);

                    $currentPage = $pagination['CurrentPage'] ?? $page;
                    $pagesTotal = $pagination['PagesTotal'] ?? $page;
                    $page++;
                } while ($currentPage < $pagesTotal);

                if ($totalSynced > 0) {
                    $summary[] = [
                        'technician_id' => $technician->id,
                        'status' => 'success',
                        'synced_items' => $totalSynced
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Technician stock sync completed',
                'results' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to sync stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncCustomTechnicianStock($id, $item_number)
    {
        SyncTechnicianStockJob::dispatch($id);

        return response()->json([
            'status' => true,
            'message' => 'Technician stock sync started in background'
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'items' => 'nullable|array',
            'items.*.item_id' => 'nullable|integer',
            'items.*.item_number' => 'nullable|string',
            'items.*.quantity' => 'required_with:items|integer|min:0',

            'parts' => 'nullable|array',
            'parts.*.part_id' => 'nullable|integer',
            'parts.*.item_number' => 'nullable|string',
            'parts.*.quantity' => 'required_with:parts|integer|min:0',
        ]);

        $results = [];

        if (!empty($request->items)) {
            $results['items'] = $this->userStockRepository->bulkUpdateStockQuantities('item', $request->items);
        }

        if (!empty($request->parts)) {
            $results['parts'] = $this->userStockRepository->bulkUpdateStockQuantities('part', $request->parts);
        }

        $allSuccess = collect($results)->every(fn($res) => $res['status'] ?? false);

        return response()->json([
            'status' => $allSuccess,
            'data'   => $results,
        ], $allSuccess ? 200 : 400);
    }



    public function syncItems(Request $request)
    {
        $request->validate([
            'item_numbers' => 'required|array|min:1',
            'item_numbers.*' => 'string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $techIdentifier = $user->tech_id ?? $user->technician_rec_id ?? null;
        if (!$techIdentifier) {
            return response()->json(['message' => 'Technician identifier not found'], 400);
        }

        $results = $this->userStockRepository->syncMultipleItems($request->item_numbers, $techIdentifier);

        return response()->json([
            'message' => 'Sync queued for requested items.',
            'results' => $results,
        ]);
    }

    // Export user stock to Excelpublic function exportUserStock($userId)
    public function exportAllUsersStock()
    {
        return Excel::download(new UserStockExport, 'all_users_stock.xlsx');
    }
}
