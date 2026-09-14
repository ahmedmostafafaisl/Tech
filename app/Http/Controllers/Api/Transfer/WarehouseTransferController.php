<?php

namespace App\Http\Controllers\Api\Transfer;

use Illuminate\Http\Request;
use App\Models\WarehouseTransfer;
use App\Http\Controllers\Controller;
use App\Services\WarehouseTransferFillService;
use App\Http\Resources\Transfer\WarehouseTransferResource;
use App\Http\Requests\Transfer\StoreWarehouseTransferRequest;
use App\Http\Requests\Transfer\UpdateWarehouseTransferRequest;
use App\Http\Resources\Transfer\SingleWarehouseTransferResource;
use App\Repositories\Interfaces\WarehouseTransferRepositoryInterface;
use Aws\Waiter;

class WarehouseTransferController extends Controller
{

    protected $repo;
    protected WarehouseTransferFillService $service;

    public function __construct(WarehouseTransferRepositoryInterface $repo, WarehouseTransferFillService $service)
    {
        $this->repo = $repo;
        $this->service = $service;
    }


    public function index()
    {
        $transfers = $this->repo->index();
        return response()->json([
            'status' => 'success',
            'data' => WarehouseTransferResource::collection($transfers),
        ]);
    }

    public function store(StoreWarehouseTransferRequest $request)
    {
        $transfer = $this->service->storeTransfer($request->validated());

        return new WarehouseTransferResource($transfer);
    }


    public function update(UpdateWarehouseTransferRequest $request,   $id)
    {
        $transfer = WarehouseTransfer::where('id', $id)->first();
        if (!$transfer) {
            return response()->json(['message' => 'transfer Not found .'], 400);
        }
        $updated = $this->service->updateTransfer($transfer, $request->validated());

        return new WarehouseTransferResource($updated);
    }

    public function show($id)
    {
        $transfer = $this->repo->find($id);
        return response()->json([
            'status' => 'success',
            'data' => new SingleWarehouseTransferResource($transfer),
        ]);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['message' => 'Warehouse transfer deleted successfully.']);
    }

    public function getMyRequests(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->type !== 'tech') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only technicians can access this resource.',
            ], 401);
        }

        $status = $request->query('status'); // 'today', 'previous', or null

        $transfers = $this->repo->getRequestsForTech($user->id, $status);

        return response()->json([
            'status' => 'success',
            'data' => WarehouseTransferResource::collection($transfers),
        ]);
    }
}
