<?php

namespace App\Http\Controllers\Api\AppointmentTransaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentTransaction\StoreAppointmentTransactionLineRequest;
use App\Http\Requests\AppointmentTransaction\StoreAppointmentTransactionRequest;
use App\Http\Requests\AppointmentTransaction\StoreAppointmentTransactionSerialRequest;
use App\Http\Requests\AppointmentTransaction\UpdateAppointmentTransactionLineRequest;
use App\Http\Requests\AppointmentTransaction\UpdateAppointmentTransactionRequest;
use App\Http\Requests\AppointmentTransaction\UpdateAppointmentTransactionSerialRequest;
use App\Http\Resources\AppointmentTransaction\AppointmentTransactionLineResource;
use App\Http\Resources\AppointmentTransaction\AppointmentTransactionResource;
use App\Http\Resources\AppointmentTransaction\AppointmentTransactionSerialResource;
use App\Repositories\Interfaces\AppointmentTransactionInterface;
use Illuminate\Http\Request;

class AppointmentTransactionController extends Controller
{
    public function __construct(
        private readonly AppointmentTransactionInterface $repository,
    ) {}

    // ===== INDEX =====
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->input('per_page', 15));

        // Support both "current_page" and "page" query params
        $currentPage = max(1, (int) $request->input(
            'current_page',
            $request->input('page', 1)
        ));

        $transactions = $this->repository->getAll(
            $request->only(['tech_id', 'book_id']),
            $perPage,
            $currentPage
        );

        return response()->json([
            'status' => true,
            'data'   => AppointmentTransactionResource::collection($transactions),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'total_pages'  => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
                'total_items'  => $transactions->total(),
            ],
        ]);
    }

    // ===== SHOW =====
    public function show(int $id)
    {
        $transaction = $this->repository->findById($id);

        if (!$transaction) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new AppointmentTransactionResource($transaction),
        ]);
    }

    // ===== STORE =====
    public function store(StoreAppointmentTransactionRequest $request)
    {
        $transaction = $this->repository->create($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Created successfully.',
            'data'    => new AppointmentTransactionResource($transaction),
        ], 201);
    }

    // ===== UPDATE =====
    public function update(UpdateAppointmentTransactionRequest $request, int $id)
    {
        $transaction = $this->repository->update($id, $request->validated());

        if (!$transaction) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Updated successfully.',
            'data'    => new AppointmentTransactionResource($transaction),
        ]);
    }

    // ===== DESTROY =====
    public function destroy(int $id)
    {
        if (!$this->repository->delete($id)) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Deleted successfully.']);
    }

    // ===== LINE INDEX =====
    public function indexLines(int $transactionId)
    {
        $lines = $this->repository->getLinesByTransaction($transactionId);

        if ($lines === null) {
            return response()->json(['status' => false, 'message' => 'Transaction not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => AppointmentTransactionLineResource::collection($lines),
        ]);
    }

    // ===== LINE SHOW =====
    public function showLine(int $lineId)
    {
        $line = $this->repository->findLineById($lineId);

        if (!$line) {
            return response()->json(['status' => false, 'message' => 'Line not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new AppointmentTransactionLineResource($line),
        ]);
    }

    // ===== LINE STORE =====
    public function storeLine(StoreAppointmentTransactionLineRequest $request, int $transactionId)
    {
        $line = $this->repository->storeLine($transactionId, $request->validated());

        if (!$line) {
            return response()->json(['status' => false, 'message' => 'Transaction not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Line created successfully.',
            'data'    => new AppointmentTransactionLineResource($line),
        ], 201);
    }

    // ===== LINE UPDATE =====
    public function updateLine(UpdateAppointmentTransactionLineRequest $request, int $lineId)
    {
        $line = $this->repository->updateLine($lineId, $request->validated());

        if (!$line) {
            return response()->json(['status' => false, 'message' => 'Line not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Line updated successfully.',
            'data'    => new AppointmentTransactionLineResource($line),
        ]);
    }

    // ===== LINE DESTROY =====
    public function destroyLine(int $lineId)
    {
        if (!$this->repository->deleteLine($lineId)) {
            return response()->json(['status' => false, 'message' => 'Line not found.'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Line deleted successfully.']);
    }

    // ===== SERIAL INDEX =====
    public function indexSerials(int $lineId)
    {
        $serials = $this->repository->getSerialsByLine($lineId);

        if ($serials === null) {
            return response()->json(['status' => false, 'message' => 'Line not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => AppointmentTransactionSerialResource::collection($serials),
        ]);
    }

    // ===== SERIAL SHOW =====
    public function showSerial(int $serialId)
    {
        $serial = $this->repository->findSerialById($serialId);

        if (!$serial) {
            return response()->json(['status' => false, 'message' => 'Serial not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new AppointmentTransactionSerialResource($serial),
        ]);
    }

    // ===== SERIAL STORE =====
    public function storeSerial(StoreAppointmentTransactionSerialRequest $request, int $lineId)
    {
        $serial = $this->repository->storeSerial($lineId, $request->validated());

        if (!$serial) {
            return response()->json(['status' => false, 'message' => 'Line not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Serial created successfully.',
            'data'    => new AppointmentTransactionSerialResource($serial),
        ], 201);
    }

    // ===== SERIAL UPDATE =====
    public function updateSerial(UpdateAppointmentTransactionSerialRequest $request, int $serialId)
    {
        try {
            $serial = $this->repository->updateSerial($serialId, $request->validated());
        } catch (\App\Exceptions\DuplicateSerialException $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if (!$serial) {
            return response()->json(['status' => false, 'message' => 'Serial not found.'], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Serial updated successfully.',
            'data'    => new AppointmentTransactionSerialResource($serial),
        ]);
    }

    // ===== SERIAL DESTROY =====
    public function destroySerial(int $serialId)
    {
        if (!$this->repository->deleteSerial($serialId)) {
            return response()->json(['status' => false, 'message' => 'Serial not found.'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Serial deleted successfully.']);
    }
}
