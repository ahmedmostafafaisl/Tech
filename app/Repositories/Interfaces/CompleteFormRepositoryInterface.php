<?php

namespace App\Repositories\Interfaces;

use App\Models\CompleteForm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompleteFormRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function findOrFail(int $id): CompleteForm;

    public function create(array $data): CompleteForm;

    public function update(CompleteForm $completeForm, array $data): CompleteForm;

    public function delete(CompleteForm $completeForm): bool;
}
