<?php


namespace App\Repositories\Interfaces;




interface EmergencyItemRepositoryInterface
{
    public function store(array $data, int $appointmentId);

    public function update(int $appointmentId, array $data);

    public function getItemsByAppointment(int $appointmentId);
    public function show($id);

    public function getItemHistory($itemId);
    public function getClientItems($appointmentId);
    // attachments
    public function attachItemsWithPartsToAppointment(array $data): mixed;
    public function deleteEmergencyItemWithParts(int $itemId, string $type): mixed;
    public function attachPartsToEmergencyItem(array $data): mixed;
}
