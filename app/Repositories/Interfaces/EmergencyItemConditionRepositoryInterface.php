<?php

namespace App\Repositories\Interfaces;


interface EmergencyItemConditionRepositoryInterface
{
    public function store(array $data);
    public function update(int $id, array $data);
    public function getByMainItemId(int $mainItemId);
    public function getConditionsByAppointment(int $appointmentId);

    public function deleteByMainItem(int $emergencyMainItemId): bool;
}
