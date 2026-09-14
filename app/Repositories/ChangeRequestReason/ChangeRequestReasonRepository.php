<?php

namespace App\Repositories\ChangeRequestReason;

use App\Models\ChangeRequestReason;
use App\Repositories\Interfaces\ChangeRequestReasonInterface;
use Illuminate\Database\Eloquent\Collection;

class ChangeRequestReasonRepository implements ChangeRequestReasonInterface
{
    public function getAll(?string $type): Collection
    {
        return ChangeRequestReason::when($type, fn($q) => $q->where('reason_type', $type))->get();
    }

    public function findById(int $id): ?ChangeRequestReason
    {
        return ChangeRequestReason::find($id);
    }

    public function create(array $data): ChangeRequestReason
    {
        return ChangeRequestReason::create($data);
    }

    public function update(int $id, array $data): ?ChangeRequestReason
    {
        $reason = $this->findById($id);

        if (!$reason) {
            return null;
        }

        $reason->update($data);

        return $reason->fresh();
    }

    public function delete(int $id): bool
    {
        $reason = $this->findById($id);

        if (!$reason) {
            return false;
        }

        return $reason->delete();
    }

    public function syncFromDy(array $reasons): void
    {
        foreach ($reasons as $reason) {
            ChangeRequestReason::firstOrCreate(
                ['reason_rec_id' => $reason['ReasonRecId']],
                [
                    'reason_type' => $reason['ReasonType'],
                    'reason'      => $reason['Reason'],
                    'title_ar'    => 'اضف ملاحظاتك هنا',
                    'title_en'    => 'Add your notes here',
                ]
            );
        }
    }
}
