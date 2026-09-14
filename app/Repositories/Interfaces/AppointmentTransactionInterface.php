<?php

namespace App\Repositories\Interfaces;

use App\Models\AppointmentTransaction;
use App\Models\AppointmentTransactionLine;
use App\Models\AppointmentTransactionSerial;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AppointmentTransactionInterface
{
    public function getAll(array $filters, int $perPage, int $page = 1): LengthAwarePaginator;
    public function findById(int $id): ?AppointmentTransaction;
    public function create(array $data): AppointmentTransaction;
    public function update(int $id, array $data): ?AppointmentTransaction;
    public function delete(int $id): bool;

    // Lines
    public function getLinesByTransaction(int $transactionId): ?Collection;
    public function findLineById(int $lineId): ?AppointmentTransactionLine;
    public function storeLine(int $transactionId, array $data): mixed;
    public function updateLine(int $lineId, array $data): ?AppointmentTransactionLine;
    public function deleteLine(int $lineId): bool;

    // Serials
    public function getSerialsByLine(int $lineId): ?Collection;
    public function findSerialById(int $serialId): ?AppointmentTransactionSerial;
    public function storeSerial(int $lineId, array $data): mixed;
    public function updateSerial(int $serialId, array $data): ?AppointmentTransactionSerial;
    public function deleteSerial(int $serialId): bool;
}
