<?php

namespace App\Repositories\Interfaces;

use App\Models\ChangeRequestReason;
use Illuminate\Database\Eloquent\Collection;

interface ChangeRequestReasonInterface
{
    public function getAll(?string $type): Collection;
    public function findById(int $id): ?ChangeRequestReason;
    public function create(array $data): ChangeRequestReason;
    public function update(int $id, array $data): ?ChangeRequestReason;
    public function delete(int $id): bool;
    public function syncFromDy(array $reasons): void;
}
