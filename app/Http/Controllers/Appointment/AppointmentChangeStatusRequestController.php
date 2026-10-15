<?php

namespace App\Http\Controllers\Appointment;

use App\Models\Appointment;
use App\Models\User;
use App\Services\DY365\DyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentChangeStatusRequest\StoreAppointmentChangeStatusRequestRequest;
use App\Http\Requests\AppointmentChangeStatusRequest\UpdateAppointmentChangeStatusRequestRequest;
use App\Repositories\Interfaces\AppointmentChangeStatusRequestInterface;
use App\Http\Resources\Appointment\AppointmentChangeStatusRequestResource;

class AppointmentChangeStatusRequestController extends Controller
{
    protected $repo;
    protected $dyService;

    public function __construct(DyService $dynamicsService, AppointmentChangeStatusRequestInterface $repo)
    {
        $this->repo = $repo;
        $this->dyService = $dynamicsService;
    }

    /**
     * The unscoped, every-request view — myRequests() below is the self-service equivalent. Admin-only:
     * previously reachable by any authenticated user, including every technician.
     */
    public function index()
    {
        $this->authorizeAdmin();

        return AppointmentChangeStatusRequestResource::collection($this->repo->all());
    }

    /**
     * store()/update() previously called $request->validated() on a plain Illuminate\Http\Request, which
     * has no such method — every call threw a fatal error. Both now use a real FormRequest.
     *
     * technician_id, customer_id and sales_order_id are derived from the appointment record itself, never
     * accepted from the request body: otherwise any technician could file a change request that appears to
     * belong to a different technician's job or a different customer.
     */
    public function store(StoreAppointmentChangeStatusRequestRequest $request)
    {
        $validated = $request->validated();

        $appointment = Appointment::findOrFail($validated['appointment_id']);

        if ((int) $appointment->technician_id !== (int) auth()->id()) {
            abort(403, 'You are not authorized to file a change request for this appointment.');
        }

        $data = $this->repo->create([
            'appointment_id'  => $appointment->id,
            'technician_id'   => auth()->id(),
            'customer_id'     => $appointment->customer_id,
            'sales_order_id'  => $appointment->sales_order_id,
            'type'            => $validated['type'],
            'description'     => $validated['description'] ?? null,
            'status'          => 'pending',
        ]);

        return new AppointmentChangeStatusRequestResource($data);
    }

    /** Own request, or an admin. Previously reachable by any authenticated user for any request id. */
    public function show($id)
    {
        $model = $this->repo->find($id);

        $this->authorizeOwnerOrAdmin($model);

        return new AppointmentChangeStatusRequestResource($model);
    }

    /** Same ownership rule as show(). */
    public function update(UpdateAppointmentChangeStatusRequestRequest $request, $id)
    {
        $model = $this->repo->find($id);

        $this->authorizeOwnerOrAdmin($model);

        $data = $this->repo->update($id, $request->validated());
        return new AppointmentChangeStatusRequestResource($data);
    }

    /** Same ownership rule as show(). Previously reachable by any authenticated user for any request id. */
    public function destroy($id)
    {
        $model = $this->repo->find($id);

        $this->authorizeOwnerOrAdmin($model);

        $this->repo->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'You are not authorized to perform this action.');
        }
    }

    private function authorizeOwnerOrAdmin(\App\Models\AppointmentChangeStatusRequest $model): void
    {
        $isOwner = (int) $model->technician_id === (int) auth()->id();
        $isAdmin = auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'You are not authorized to access this change request.');
        }
    }

    // Get my requests
    public function myRequests()
    {
        $requests = $this->repo->getMyRequests();
        return response()->json([
            'status' => true,
            'data' => AppointmentChangeStatusRequestResource::collection($requests),
        ]);
    }

    // Sync all technician change status requests
    /**
     * Previously returned inside the first loop iteration — only ever the first technician with a
     * technician_rec_id was processed, win or lose, and every other technician was silently skipped. Now
     * runs for all of them and reports which succeeded and which didn't, the same pattern already used
     * elsewhere in this codebase for a per-item sync loop (see DyController::completeSuccessPayments /
     * deleteAppointmentTransactionSerial).
     */
    public function syncAllTechnicianChangeStatusRequests()
    {
        $users = User::where('type', 'tech')->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No technicians found.'], 404);
        }

        $synced = [];
        $failed = [];

        foreach ($users as $technician) {
            if (!$technician->technician_rec_id) {
                continue;
            }

            try {
                $response = $this->dyService->getTechnicianChangeStatusRequests([
                    'worker' => $technician->technician_rec_id,
                ]);

                if (isset($response['Data']['Requests']) && is_array($response['Data']['Requests'])) {
                    $this->repo->syncAllTechnicianChangeStatusRequests($response['Data']['Requests']);
                    $synced[] = $technician->technician_rec_id;
                } else {
                    $failed[] = ['tech_id' => $technician->technician_rec_id, 'reason' => 'No requests found'];
                }
            } catch (\Exception $e) {
                $failed[] = ['tech_id' => $technician->technician_rec_id, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'message' => count($synced) > 0 ? 'Requests synced successfully' : 'Failed to sync Requests',
            'synced'  => $synced,
            'failed'  => $failed,
        ], count($synced) > 0 ? 200 : 500);
    }
}
