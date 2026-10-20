<?php

namespace App\Services\Serials;

use App\Models\ChangeRequest;

/**
 * Detects a DY365-reported Canceled/Cancelled appointment status and records it, once, as a local
 * ChangeRequest — so PermanentlyTransmittedSerials' simple rule (TransmittedSerial + ChangeRequest, no
 * DY365 call of its own) picks up a return/refund cancellation without needing a live DY365 status check
 * every time a serial's availability is checked. Call this wherever an appointment's current DY365 status
 * is already being fetched for some other reason (singleAppointmentByBookId, mainWarehouse when given a
 * bookId) — this method itself never calls DY365; it only reacts to data already in hand.
 *
 * A ChangeRequest created this way is request_type = false (0 => cancel, matching the existing convention —
 * see the original change_requests migration), distinguishable from a real customer-initiated one by its
 * notes text.
 */
class CancellationChangeRequestRecorder
{
    private const CANCELLED_STATUSES = ['Canceled', 'Cancelled'];

    /**
     * @param  string  $bookId  the appointment whose live DY365 data was just fetched
     * @param  array|null  $appointmentData  the 'Data' object from that DY365 response (not the whole
     *                                       envelope) — null or missing Status is simply ignored
     */
    public static function recordIfCancelled(string $bookId, ?array $appointmentData): void
    {
        if (! in_array($appointmentData['Status'] ?? null, self::CANCELLED_STATUSES, true)) {
            return;
        }

        // Already recorded — this is what makes every later lookup free of a DY365 call: once recorded, the
        // plain ChangeRequest-exists check this row satisfies is what PermanentlyTransmittedSerials reads.
        if (ChangeRequest::where('book_id', $bookId)->exists()) {
            return;
        }

        ChangeRequest::create([
            'book_id'        => $bookId,
            // sales_order_id is required (not nullable) on this table; DY365's own response should always
            // carry it, but the book_id itself is a safe, always-available fallback rather than failing to
            // record the cancellation entirely over a missing field.
            'sales_order_id' => $appointmentData['SalesOrderId'] ?? $bookId,
            'tech_id'        => is_numeric($appointmentData['Worker'] ?? null) ? (int) $appointmentData['Worker'] : null,
            'request_type'   => false,
            'notes'          => 'Auto-recorded: DY365 reports this appointment as ' . $appointmentData['Status'] . '.',
        ]);
    }
}
