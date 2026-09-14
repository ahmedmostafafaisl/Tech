<?php

namespace App\Repositories\Interfaces;


interface UserStockRepositoryInterface
{
    public function getUserStock($userId);
    public function createOrUpdateUserStock(int $userId, array $items, array $parts);

    public function delete($id);
    public function addItems(int $userId, array $items);
    public function addParts(int $userId, array $parts);

    // Sync customer stock

    public function syncTechnicianStock(array $products, int $technicianId): void;

    public function bulkUpdateStockQuantities(string $type, array $data): array;

    public function syncMultipleItems(array $itemNumbers, int $technicianId): array;
}
