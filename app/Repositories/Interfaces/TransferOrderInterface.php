<?php

namespace App\Repositories\Interfaces;

use App\Models\TransferOrder;

interface TransferOrderInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id): bool;
    public function getTransfersByTechnician(int $technicianId);
    public function syncTransferOrder(array $transfers, int $technicianId): void;
    public function query();
    public function getById(int $id);
    public function createFromApiResponse(array $response): TransferOrder;
    public function mapTechnicianStatusFromDb(array $dyTransfers): array;
    public function mapSingleTechFromDb(array $transferResponse): array;
}
