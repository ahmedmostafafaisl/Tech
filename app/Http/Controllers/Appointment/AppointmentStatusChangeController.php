<?php

namespace App\Http\Controllers\Appointment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Appointment\AppointmentStatusChangeResource;
use App\Http\Requests\Appointment\StoreAppointmentStatusChangeRequest;
use App\Http\Requests\Appointment\UpdateAppointmentStatusChangeRequest;
use App\Repositories\Interfaces\AppointmentStatusChangeRepositoryInterface;


class AppointmentStatusChangeController extends Controller
{
    protected $repository;

    public function __construct(AppointmentStatusChangeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return AppointmentStatusChangeResource::collection($this->repository->getAll());
    }

    public function store(StoreAppointmentStatusChangeRequest $request)
    {
        return new AppointmentResource($this->repository->store($request->validated()));
    }

    public function show($id)
    {
        return new AppointmentStatusChangeResource($this->repository->getById($id));
    }

    public function update(UpdateAppointmentStatusChangeRequest $request, $id)
    {
        return new AppointmentStatusChangeResource($this->repository->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Deleted successfully'], 200);
    }
}
