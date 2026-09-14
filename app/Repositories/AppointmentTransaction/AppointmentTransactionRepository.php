<?php

namespace App\Repositories\AppointmentTransaction;

use App\Models\AppointmentTransaction;
use App\Models\AppointmentTransactionLine;
use App\Models\AppointmentTransactionSerial;
use App\Repositories\Interfaces\AppointmentTransactionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AppointmentTransactionRepository implements AppointmentTransactionInterface
{
    public function getAll(array $filters, int $perPage, int $page = 1): LengthAwarePaginator
    {
        return AppointmentTransaction::with(['lines.serials'])
            ->when(!empty($filters['tech_id']), fn($q) => $q->where('tech_id', $filters['tech_id']))
            ->when(!empty($filters['book_id']), fn($q) => $q->where('book_id', $filters['book_id']))
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById(int $id): ?AppointmentTransaction
    {
        return AppointmentTransaction::with(['lines.serials'])->find($id);
    }

    public function create(array $data): AppointmentTransaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = AppointmentTransaction::create([
                'book_id' => $data['book_id'],
                'rec_id'  => $data['rec_id'],
                'tech_id' => $data['tech_id'],
            ]);

            foreach ($data['lines'] ?? [] as $lineData) {
                $line = $transaction->lines()->create([
                    'sales_line_rec_id' => $lineData['sales_line_rec_id'],
                    'item_number'       => $lineData['item_number'],
                    'quantity'          => $lineData['quantity'],
                    'order_type_rec_id' => $lineData['order_type_rec_id'],
                    'warranty_status'   => $lineData['warranty_status'],
                ]);

                foreach ($lineData['serials'] ?? [] as $serialData) {
                    $line->serials()->create([
                        'sales_line_rec_id' => $lineData['sales_line_rec_id'],
                        'item_number'       => $lineData['item_number'],
                        'serial'            => $serialData['serial'],
                    ]);
                }
            }

            return $transaction->load('lines.serials');
        });
    }

    public function update(int $id, array $data): ?AppointmentTransaction
    {
        $transaction = $this->findById($id);

        if (!$transaction) {
            return null;
        }

        $transaction->update($data);

        return $transaction->fresh('lines.serials');
    }

    public function delete(int $id): bool
    {
        $transaction = AppointmentTransaction::find($id);

        if (!$transaction) {
            return false;
        }

        return $transaction->delete();
    }

    // ===== LINES =====

    public function getLinesByTransaction(int $transactionId): ?Collection
    {
        $transaction = AppointmentTransaction::find($transactionId);

        if (!$transaction) {
            return null;
        }

        return $transaction->lines()->with('serials')->get();
    }

    public function findLineById(int $lineId): ?AppointmentTransactionLine
    {
        return AppointmentTransactionLine::with('serials')->find($lineId);
    }

    public function storeLine(int $transactionId, array $data): mixed
    {
        $transaction = AppointmentTransaction::find($transactionId);

        if (!$transaction) {
            return null;
        }

        return $transaction->lines()->create($data);
    }

    public function updateLine(int $lineId, array $data): ?AppointmentTransactionLine
    {
        $line = AppointmentTransactionLine::find($lineId);

        if (!$line) {
            return null;
        }

        $line->update($data);

        return $line->fresh('serials');
    }

    public function deleteLine(int $lineId): bool
    {
        $line = AppointmentTransactionLine::find($lineId);

        if (!$line) {
            return false;
        }

        return $line->delete();
    }

    // ===== SERIALS =====

    public function getSerialsByLine(int $lineId): ?Collection
    {
        $line = AppointmentTransactionLine::find($lineId);

        if (!$line) {
            return null;
        }

        return $line->serials()->get();
    }

    public function findSerialById(int $serialId): ?AppointmentTransactionSerial
    {
        return AppointmentTransactionSerial::find($serialId);
    }

    public function storeSerial(int $lineId, array $data): mixed
    {
        $line = AppointmentTransactionLine::find($lineId);

        if (!$line) {
            return null;
        }

        return $line->serials()->create([
            'sales_line_rec_id' => $line->sales_line_rec_id,
            'item_number'       => $line->item_number,
            'serial'            => $data['serial'],
        ]);
    }

    public function updateSerial(int $serialId, array $data): ?AppointmentTransactionSerial
    {
        $serial = AppointmentTransactionSerial::find($serialId);

        if (!$serial) {
            return null;
        }

        try {
            $serial->update($data);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            throw new \App\Exceptions\DuplicateSerialException(
                'This serial number is already registered for this item.',
                previous: $e
            );
        }

        return $serial->fresh();
    }

    public function deleteSerial(int $serialId): bool
    {
        $serial = AppointmentTransactionSerial::find($serialId);

        if (!$serial) {
            return false;
        }

        return $serial->delete();
    }
}
