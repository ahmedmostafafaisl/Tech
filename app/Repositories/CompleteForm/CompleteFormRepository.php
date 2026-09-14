<?php

namespace App\Repositories\CompleteForm;

use App\Models\CompleteForm;
use App\Repositories\Interfaces\CompleteFormRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompleteFormRepository implements CompleteFormRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CompleteForm::query()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): CompleteForm
    {
        return CompleteForm::query()->findOrFail($id);
    }

    public function create(array $data): CompleteForm
    {
        return CompleteForm::query()->create($data);
    }

    public function update(CompleteForm $completeForm, array $data): CompleteForm
    {
        $completeForm->update($data);
        return $completeForm->refresh();
    }

    public function delete(CompleteForm $completeForm): bool
    {
        return (bool) $completeForm->delete();
    }
}
