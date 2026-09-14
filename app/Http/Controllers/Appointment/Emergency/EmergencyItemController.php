<?php

namespace App\Http\Controllers\Appointment\Emergency;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Emergency\StoreEmergencyItemRequest;
use App\Http\Requests\Emergency\AttachEmergencyItemRequest;
use App\Http\Resources\Emergency\EmergencyMainItemResource;
use App\Http\Requests\Emergency\AttachPartToEmergencyItemRequest;
use App\Repositories\Interfaces\EmergencyItemRepositoryInterface;

class EmergencyItemController extends Controller
{
    protected $repo;

    public function __construct(EmergencyItemRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function store(StoreEmergencyItemRequest $request, $id)
    {
        return   $this->repo->store($request->validated(), $id);
    }

    public function update(StoreEmergencyItemRequest $request, int $id)
    {
        return  $this->repo->update($id, $request->validated());
    }


    public function appointmentItems(int $appointmentId)
    {
        return $this->repo->getItemsByAppointment($appointmentId);
    }

    public function show(int $id)
    {
        return    $this->repo->show($id);
    }

    public function getItemHistory($itemId)
    {
        return    $this->repo->getItemHistory($itemId);
    }
    public function getClientItems($appointmentId)
    {
        return  $this->repo->getClientItems($appointmentId);
    }

    // Attachments


    public function attachItemsWithParts(AttachEmergencyItemRequest $request)
    {
        return $this->repo->attachItemsWithPartsToAppointment($request->validated());
    }

    public function deleteItemWithParts($id, Request $request)
    {
        $request->validate([
            'type' => 'required|in:periodic,emergency',
        ]);
        $type = $request->input('type');
        return $this->repo->deleteEmergencyItemWithParts($id, $type);
    }

    public function attachParts(AttachPartToEmergencyItemRequest $request)
    {
        return $this->repo->attachPartsToEmergencyItem($request->validated());
    }
}
