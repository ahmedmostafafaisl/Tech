<?php

namespace App\Services\Serials;

use App\Models\AppointmentTransactionSerial;
use App\Models\DirectAppointment;
use App\Models\TransmittedSerial;
use Illuminate\Support\Facades\DB;

/**
 * Populates the transmitted_serials fast-lookup table from one appointment's own dy_body, at the moment
 * dy_body is written — i.e. the moment the appointment is actually SENT to DY365, regardless of whether
 * DY365 goes on to accept or reject it. See CompleteSuccessPaymentsCommand, the only place this should be
 * called from a live completion, and the console command below it for a one-time historical backfill.
 *
 * dy_body's SalesLinesSerial carries only a serial value and a DY sales-line id — no item_number — so this
 * resolves item_number the same way TransmittedSerials::filter() does elsewhere: by matching the serial
 * value against this SAME book_id's own appointment_transaction_serials rows, which do carry it.
 */
class TransmittedSerialIndexer
{
    /** @return int how many (book_id, serial) rows were written (new or already-present, upserted either way) */
    public static function syncForAppointment(DirectAppointment $appointment): int
    {
        if (blank($appointment->book_id)) {
            return 0;
        }

        $transmitted = TransmittedSerials::extract($appointment->dy_body);

        if ($transmitted === null || $transmitted === []) {
            return 0;
        }

        $itemNumberBySerial = AppointmentTransactionSerial::query()
            ->join('appointment_transaction_lines', 'appointment_transaction_lines.id', '=', 'appointment_transaction_serials.appointment_transaction_line_id')
            ->join('appointment_transactions', 'appointment_transactions.id', '=', 'appointment_transaction_lines.appointment_transaction_id')
            ->where('appointment_transactions.book_id', $appointment->book_id)
            ->whereIn('appointment_transaction_serials.serial', $transmitted)
            ->pluck('appointment_transaction_serials.item_number', 'appointment_transaction_serials.serial');

        $rows = [];
        $now  = now();

        foreach ($transmitted as $serial) {
            $rows[] = [
                'book_id'     => $appointment->book_id,
                'tech_id'     => $appointment->tech_id,
                'serial'      => $serial,
                'item_number' => $itemNumberBySerial[$serial] ?? null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if ($rows === []) {
            return 0;
        }

        // upsert: re-running this for the same appointment (e.g. the backfill command, or a retry) must
        // never create duplicate rows or fail on the (book_id, serial) unique constraint.
        DB::table('transmitted_serials')->upsert(
            $rows,
            ['book_id', 'serial'],
            ['item_number', 'tech_id', 'updated_at']
        );

        return count($rows);
    }
}
