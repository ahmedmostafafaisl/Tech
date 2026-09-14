<?php

namespace App\Http\Controllers\Api\Transfer;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\CreateTransferOrderRequest;
use App\Http\Requests\Transfer\DeleteTransferOrderRequest;
use App\Http\Requests\Transfer\NewCreateTransferOrderRequest;
use App\Http\Requests\Transfer\TransferOrderRequest;
use App\Http\Requests\Transfer\UpdateTransferOrderRequest;
use App\Http\Resources\Transfer\NewTransferOrderResource;
use App\Http\Resources\Transfer\TransferOrderResource;
use App\Models\User;
use App\Repositories\Interfaces\TransferOrderInterface;
use App\Services\DY365\DyService;
use App\Services\TransferOrder\TransferOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransferOrderController extends Controller
{
    use ApiResponseHelper;
    protected $transferOrderRepo;
    protected DyService $dyService;
    protected  $service;



    public function __construct(DyService $dynamicsService, TransferOrderInterface $transferOrderRepo, TransferOrderService $service)
    {
        $this->transferOrderRepo = $transferOrderRepo;
        $this->dyService = $dynamicsService;
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filter = $request->query('filter');

        $orders = $this->transferOrderRepo->query();

        if ($filter === 'today') {
            $orders->whereDate('date', now()->toDateString());
        } elseif ($filter === 'previous') {
            $orders->whereDate('date', '<', now()->toDateString());
        }

        return TransferOrderResource::collection($orders->get());
    }


    public function store(TransferOrderRequest $request)
    {
        $order = $this->transferOrderRepo->create($request->validated());
        return new TransferOrderResource($order);
    }

    public function show($id)
    {
        $order = $this->transferOrderRepo->find($id);
        return new TransferOrderResource($order);
    }

    public function update(TransferOrderRequest $request, $id)
    {
        $order = $this->transferOrderRepo->update($id, $request->validated());
        return new TransferOrderResource($order);
    }

    public function destroy($id)
    {
        $this->transferOrderRepo->delete($id);
        return response()->json(['message' => 'Transfer order deleted successfully']);
    }

    public function syncAllTechnicianTransfers()
    {
        try {
            $users = User::where('type', 'tech')->get();

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No technicians found.'], 404);
            }

            foreach ($users as $technician) {
                if (!$technician->technician_rec_id) {
                    continue;
                }
                $payload = [
                    'worker' => $technician->technician_rec_id,
                ];
                $response = $this->dyService->getTechnicianTransfers($payload);

                if (isset($response['Data']['TransferOrders']) && is_array($response['Data']['TransferOrders'])) {
                    $this->transferOrderRepo->syncTransferOrder($response['Data']['TransferOrders'], $technician->id);
                    return response()->json(['message' => 'Transfers synced successfully']);
                } else {

                    continue;
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to sync Transfers: ' . $e->getMessage()], 500);
        }
    }
    public function syncCustomTechnicianTransfers($id)
    {
        try {
            $users = User::where('id', $id)->where('type', 'tech')->get();

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No technicians found.'], 404);
            }

            foreach ($users as $technician) {
                if (!$technician->technician_rec_id) {
                    continue;
                }
                $payload = [
                    'worker' => $technician->technician_rec_id,
                ];
                $response = $this->dyService->getTechnicianTransfers($payload);

                if (isset($response['Data']['TransferOrders']) && is_array($response['Data']['TransferOrders'])) {
                    $this->transferOrderRepo->syncTransferOrder($response['Data']['TransferOrders'], $technician->id);
                    return response()->json(['message' => 'Transfers synced successfully']);
                } else {

                    continue;
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to sync Transfers: ' . $e->getMessage()], 500);
        }
    }
    public function getTechnicianTransfers()
    {
        $user = auth()->user();

        // Check if the authenticated user is a technician
        if ($user->type !== 'tech') {
            return response()->json([
                'message' => 'Unauthorized. Only technicians can access their transfers.'
            ], 403);
        }

        // Get transfers for the authenticated technician
        $transfers = $this->transferOrderRepo->getTransfersByTechnician($user->id);

        return TransferOrderResource::collection($transfers);
    }

    public function newTechnicianTransfers(Request $request)
    {
        $filter = $request->query('filter');
        $user   = auth()->user(); // Get logged in user
        // Check if the authenticated user is a technician
        if ($user->type !== 'tech') {
            return response()->json([
                'message' => 'Unauthorized. Only technicians can access their transfers.'
            ], 403);
        }

        $orders = $this->transferOrderRepo->query()
            ->where('tech_id', $user->id);

        if ($filter === 'today') {
            $orders->whereDate('date', now()->toDateString());
        } elseif ($filter === 'previous') {
            $orders->whereDate('date', '<', now()->toDateString());
        }

        return TransferOrderResource::collection($orders->get());
    }


    // new methods can be added here

    public function newStore(NewCreateTransferOrderRequest $request)
    {
        return   $transfer = $this->service->createTransfer($request->validated());
    }

    public function newUpdate(UpdateTransferOrderRequest $request)
    {

        $order = $this->service->updateStatus($request->validated());
        if (!$order || $order['Code'] === 400 || $order['Status'] === false) {
            return response()->json($order ?? [
                'Status' => false,
                'Error' => $order['Error'] ?? 'Transfer order not found',
                'Code' => 404
            ], 404);
        }
        return ($order);
    }

    public function newDestroy(DeleteTransferOrderRequest $request)
    {
        return  $this->service->delete($request->transferOrderId);
    }

    // tech transfers
    public function getTransferOrders($tech_id, Request $request)
    {

        $response = $this->service->getTechnicianTransfersWithLocalStatus($tech_id, $request->input('currentPage', 1), $request->input('pageSize', 30), $request->input('searchTerm', ''));

        return $this->setCode(200)
            ->setData($response)
            ->setMessage('Success.')
            ->send();
    }

    public function getSingleTransferOrders($tech_id, $transferOrderId)
    {
        return   $response = $this->service->getSingleTransferAndSyncTechStatus($tech_id, $transferOrderId);
    }
}
