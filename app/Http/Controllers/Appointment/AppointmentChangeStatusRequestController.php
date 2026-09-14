<?php

namespace App\Http\Controllers\Appointment;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\DY365\DyService;
use App\Http\Controllers\Controller;
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

    public function index()
    {
        return AppointmentChangeStatusRequestResource::collection($this->repo->all());
    }

    public function store(Request $request)
    {
        $data = $this->repo->create($request->validated());
        return new AppointmentChangeStatusRequestResource($data);
    }

    public function show($id)
    {
        return new AppointmentChangeStatusRequestResource($this->repo->find($id));
    }

    public function update(Request $request, $id)
    {
        $data = $this->repo->update($id, $request->validated());
        return new AppointmentChangeStatusRequestResource($data);
    }

    public function destroy($id)
    {
        $this->repo->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
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
    public function syncAllTechnicianChangeStatusRequests()
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
                $response = $this->dyService->getTechnicianChangeStatusRequests($payload);

                if (isset($response['Data']['Requests']) && is_array($response['Data']['Requests'])) {
                    $this->repo->syncAllTechnicianChangeStatusRequests($response['Data']['Requests']);
                    return response()->json(['message' => 'Requests synced successfully']);
                } else {

                    return response()->json(['message' => 'Failed to sync Requests: No products found'], 500);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to sync Requests: ' . $e->getMessage()], 500);
        }
    }
}
