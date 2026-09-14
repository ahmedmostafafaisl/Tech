<?php

namespace App\Http\Controllers\Api\ChangeRequestReason;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeRequestReason\StoreChangeRequestReasonRequest;
use App\Http\Requests\ChangeRequestReason\UpdateChangeRequestReasonRequest;
use App\Http\Resources\ChangeRequestReason\ChangeRequestReasonResource;
use App\Repositories\Interfaces\ChangeRequestReasonInterface;
use App\Services\DY365\DyService;
use Illuminate\Http\Request;

class ChangeRequestReasonController extends Controller
{
    public function __construct(
        private readonly ChangeRequestReasonInterface $repository,
        private readonly DyService $dyService,
    ) {}

    // ===== INDEX =====
    public function index(Request $request)
    {
        $response = $this->dyService->getChangeRequestReasons();

        if (
            isset($response['Status']) && $response['Status'] === true &&
            isset($response['Data']['ReasonsList'])
        ) {
            $this->repository->syncFromDy($response['Data']['ReasonsList']);
        }

        $reasons = $this->repository->getAll($request->input('type'));

        return response()->json([
            'status'  => true,
            'reasons' => ChangeRequestReasonResource::collection($reasons),
        ]);
    }

    // ===== SHOW =====
    public function show(int $id)
    {
        $reason = $this->repository->findById($id);

        if (!$reason) {
            return response()->json([
                'status'  => false,
                'message' => 'Reason not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'reason' => new ChangeRequestReasonResource($reason),
        ]);
    }

    // ===== STORE =====
    public function store(StoreChangeRequestReasonRequest $request)
    {
        $reason = $this->repository->create([
            'reason_rec_id' => $request->reason_rec_id,
            'reason_type'   => $request->reason_type,
            'reason'        => $request->reason,
            'title_ar'      => $request->title_ar ?? 'اضف ملاحظاتك هنا',
            'title_en'      => $request->title_en ?? 'Add your notes here',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Reason created successfully.',
            'reason'  => new ChangeRequestReasonResource($reason),
        ], 201);
    }

    // ===== UPDATE =====
    public function update(UpdateChangeRequestReasonRequest $request, int $id)
    {
        $reason = $this->repository->update($id, $request->only([
            'reason_type',
            'reason',
            'title_ar',
            'title_en',
        ]));

        if (!$reason) {
            return response()->json([
                'status'  => false,
                'message' => 'Reason not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Reason updated successfully.',
            'reason'  => new ChangeRequestReasonResource($reason),
        ]);
    }

    // ===== DESTROY =====
    public function destroy(int $id)
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            return response()->json([
                'status'  => false,
                'message' => 'Reason not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Reason deleted successfully.',
        ]);
    }
}
