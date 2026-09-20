<?php


namespace App\Http\Controllers\Api\Warehouse;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Services\DY365\DyService;
use Illuminate\Http\JsonResponse;
use App\Jobs\SyncWarehouseStockJob;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Jobs\SyncTechnicianStockJob;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\Warehouse\WarehouseRequest;
use App\Http\Resources\Warehouse\WarehouseResource;
use App\Repositories\Interfaces\WarehouseInterface;
use App\Http\Resources\User\MergedUserStockResource;
use App\Http\Resources\User\MergedWarehouseResource;
use App\Repositories\Interfaces\TechRepositoryInterface;
use App\Http\Resources\Warehouse\SingleWarehouseResource;

class WarehouseController extends Controller
{

    use ApiResponseHelper;

    protected $warehouseRepo;
    protected $techRepository;
    protected DyService $dynamicsService;
    public function __construct(DyService $dynamicsService, WarehouseInterface $warehouseRepo, TechRepositoryInterface $techRepository)
    {
        $this->techRepository = $techRepository;
        $this->warehouseRepo = $warehouseRepo;
        $this->dynamicsService = $dynamicsService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => WarehouseResource::collection($this->warehouseRepo->all())
        ]);
    }

    public function store(WarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseRepo->create($request->validated());
        return response()->json(['data' => new WarehouseResource($warehouse)], 201);
    }

    public function show($id, Request $request)
    {
        $appointmentLines = collect();


        // If appointment_id is sent → load appointment lines
        if ($request->appointment_id) {
            $appointment = Appointment::with('lines')->find($request->appointment_id);

            if (!$appointment) {
                return $this->setCode(code: 404)
                    ->setData([])
                    ->setMessage('Appointment not found.')
                    ->send();
            }

            $appointmentLines = $appointment->lines;
        }

        // If request is from technician
        if ($request->flag == 'tech') {
            $user = auth()->user();
            if (!$user || $user->type !== 'tech') {
                return $this->setCode(code: 401)
                    ->setData([])
                    ->setMessage('User not authenticated Or Only technicians can access this resource.')
                    ->send();
            }

            $data = $this->techRepository->getTechStock($user);

            return $this->setCode(code: 200)
                ->setData(new MergedWarehouseResource($data, $appointmentLines))
                ->setMessage('Success.')
                ->send();
        }

        return $this->setCode(code: 200)
            ->setData(new SingleWarehouseResource($this->warehouseRepo->find($id), $appointmentLines))
            ->setMessage('Success.')
            ->send();
    }


    public function update(WarehouseRequest $request, $id): JsonResponse
    {
        $warehouse = $this->warehouseRepo->update($id, $request->validated());
        return response()->json(['data' => new WarehouseResource($warehouse)]);
    }

    public function destroy($id): JsonResponse
    {
        $this->warehouseRepo->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }

    // get all inventory
    public function getInventory(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $type = $request->input('type');
        return    $response = $this->warehouseRepo->getInventory($perPage, $page, $type);
    }

    public function syncWarehouses(Request $request)
    {
        try {
            $response = $this->dynamicsService->getWarehouses();
            $this->warehouseRepo->syncWarehouses($response['Data']['Warehouses'] ?? []);
            return response()->json(['message' => 'Warehouses synced successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to sync warehouses: ' . $e->getMessage()], 500);
        }
    }

    public function syncWarehouseStock()
    {
        try {
            $warehouses = Warehouse::all();

            if ($warehouses->isEmpty()) {
                return response()->json(['message' => 'No warehouses found.'], 404);
            }

            return   $results = [];

            foreach ($warehouses as $warehouse) {
                if (!$warehouse->rec_id) {
                    $results[] = [
                        'warehouse_id' => $warehouse->id,
                        'status' => 'skipped',
                        'reason' => 'Missing rec_id'
                    ];
                    continue;
                }

                $page = 1;
                $pageSize = 300; // adjustable
                $totalSynced = 0;

                do {
                    $payload = [
                        'warehouseId'  => $warehouse->invent_location_id,
                        'currentPage'  => $page,
                        'pageSize'     => $pageSize,
                    ];

                    $response = $this->dynamicsService->getWarehouseStock($payload);

                    $pagination = $response['Data'] ?? [];
                    $products   = $pagination['Products'] ?? [];

                    if (empty($products)) {
                        // No products on the first page means no stock for this warehouse
                        break;
                    }

                    $this->warehouseRepo->syncWarehouseStock($products);
                    $totalSynced += count($products);

                    $currentPage = $pagination['CurrentPage'] ?? $page;
                    $pagesTotal  = $pagination['PagesTotal'] ?? $page;

                    $page++;
                } while ($currentPage < $pagesTotal);

                $results[] = [
                    'warehouse_id' => $warehouse->id,
                    'status' => $totalSynced > 0 ? 'success' : 'no_products',
                    'total_synced' => $totalSynced
                ];
            }

            return response()->json([
                'message' => 'Warehouse stock sync completed.',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync stock: ' . $e->getMessage()
            ], 500);
        }
    }

    public function syncCustomWarehouseStock(Request $request)
    {
        $id = $request->warehouseId;
        if (!$id) {
            return response()->json([
                'message' => 'warehouseId parameter is required.'
            ], 400);
        }
        $item_number = $request->itemNumber;
        // dd($item_number, $id);
        Artisan::call('sync:warehouse-item-stock', [
            'warehouse_id' => $id,  // 👈 matches {warehouse_id}
            '--item' => $item_number, // 👈 matches {--item=}
        ]);

        $warehouse = Warehouse::where('invent_location_id', $id)->first();

        if ($warehouse && $warehouse->type === 'TechnicianWarehouse') {
            try {
                $user = User::where('type', 'tech')
                    ->where('warehouse_id', $id)
                    ->first();

                if ($user) {
                    Artisan::call('sync:technician-stock-item', [
                        'tech_id' => $user->tech_id,
                        '--item'  => $item_number,
                    ]);
                    // SyncTechnicianStockJob::dispatch($user->tech_id, $item_number);
                }
            } catch (\Exception $e) {
                Log::error("Failed to dispatch technician stock job for warehouse {$id}: " . $e->getMessage());
                // continue without breaking
            }
        }

        return response()->json([
            'message' => 'Warehouse stock sync started. Running in background.'
        ]);
    }

    public function getTechnicianWarehouses(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->type !== 'tech') {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('User not authenticated.')
                ->send();
        }
        $warehouses = $this->getUserWarehouses($user);
        return $this->setCode(code: 200)
            ->setData($warehouses)
            ->setMessage('Success.')
            ->send();
    }
    public function getUserWarehouses($user)
    {
        return $user->warehouses()
            ->select('warehouses.id', 'warehouses.name', 'warehouses.invent_location_id', 'warehouses.rec_id')
            ->get()
            ->map(function ($warehouse) {
                $warehouse->is_primary = (int) $warehouse->pivot->is_primary;
                unset($warehouse->pivot);
                return $warehouse;
            });
    }
}
