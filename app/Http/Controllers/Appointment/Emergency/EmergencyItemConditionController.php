<?php

namespace App\Http\Controllers\Appointment\Emergency;

use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Emergency\EmergencyItemConditionResource;
use App\Http\Requests\Emergency\StoreEmergencyItemConditionRequest;
use App\Repositories\Interfaces\EmergencyItemConditionRepositoryInterface;

class EmergencyItemConditionController extends Controller
{
    use ApiResponseHelper;

    public function __construct(private EmergencyItemConditionRepositoryInterface $repository) {}

    public function store(StoreEmergencyItemConditionRequest $request)
    {
        // return $request;
        return   $this->repository->store($request->validated());
    }

    public function update($id, StoreEmergencyItemConditionRequest $request)
    {
        return   $this->repository->update($id, $request->validated());
    }

    public function showByMainItem($mainItemId)
    {
        return  $this->repository->getByMainItemId($mainItemId);
    }


    public function getByAppointment($appointmentId)
    {
        return   $this->repository->getConditionsByAppointment($appointmentId);
    }

    public function destroyByMainItem(Request $request): JsonResponse
    {
        $request->validate([
            'emergency_main_item_id' => 'required|integer|exists:emergency_main_items,id',
        ]);

        $deleted = $this->repository->deleteByMainItem($request->emergency_main_item_id);

        if (!$deleted) {
            return $this->setCode(code: 404)->setData([])->setMessage('No conditions found for this item.')->send();
        }

        return $this->setCode(code: 200)->setData([])->setMessage('Conditions and images deleted successfully.')->send();
    }
}
