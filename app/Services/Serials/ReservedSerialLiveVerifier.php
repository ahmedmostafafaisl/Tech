<?php

namespace App\Services\Serials;

use App\Models\Setting;
use App\Services\DY365\DyService;

/**
 * For submission-time validation only (SendPaymentLinksRequest, NewCompleteAppointmentRequest) — never the
 * browsing endpoints (singleAppointmentByBookId, mainWarehouse), which stay completely free of any DY365
 * call for this purpose. Here a technician is trying to actually submit a specific, small set of serials
 * right now, so paying for a live DY365 check on just the ones that would otherwise be rejected is worth it
 * to avoid a false rejection for a serial whose reserving appointment has since been cancelled in DY365 but
 * was never recorded as a ChangeRequest (the same gap CancellationChangeRequestRecorder closes elsewhere,
 * opportunistically — this closes it proactively, but only at the moment of submission, and only for the
 * handful of book_ids actually blocking this specific request).
 *
 * Toggle: settings key 'serial_live_cancellation_check_active' (default: OFF when no row exists — this adds
 * a new DY365 call to a path that previously had none, so it stays inert until explicitly turned on via the
 * existing generic settings endpoint).
 */
class ReservedSerialLiveVerifier
{
    /**
     * @param  array<string, array<string, string>>  $reservedByItem  lower-cased item_number =>
     *                                                                [serial => reserving book_id], as
     *                                                                returned by
     *                                                                PermanentlyTransmittedSerials::reservedBookIdsByItem()
     * @return array<string, array<string, string>> the same shape, with any now-released reservations
     *                                               removed; unchanged (same object shape, same content)
     *                                               when the setting is off or there's nothing to check
     */
    public static function releaseCancelledReservations(array $reservedByItem): array
    {
        if ($reservedByItem === []) {
            return $reservedByItem;
        }

        if (! Setting::isActive('serial_live_cancellation_check_active', false)) {
            return $reservedByItem;
        }

        $distinctBookIds = collect($reservedByItem)
            ->flatMap(fn(array $serialToBookId) => array_values($serialToBookId))
            ->unique()
            ->values();

        if ($distinctBookIds->isEmpty()) {
            return $reservedByItem;
        }

        $releasedBookIds = $distinctBookIds->filter(fn(string $bookId) => self::checkAndRelease($bookId))->values();

        if ($releasedBookIds->isEmpty()) {
            return $reservedByItem;
        }

        return collect($reservedByItem)
            ->map(fn(array $serialToBookId) => collect($serialToBookId)
                ->reject(fn(string $bookId) => $releasedBookIds->contains($bookId))
                ->all())
            ->all();
    }

    /** Fetches this one book_id's live DY365 status and releases it if cancelled. A lookup that fails or is
     *  inconclusive counts as "not released" — the reservation stays in place, failing on the safe side. */
    private static function checkAndRelease(string $bookId): bool
    {
        try {
            $response = app(DyService::class)->getAppointmentByBookIdNew($bookId, 0);
        } catch (\Throwable $e) {
            return false;
        }

        if (! ($response['Status'] ?? false) || ! isset($response['Data'])) {
            return false;
        }

        $outcome = CancellationChangeRequestRecorder::recordIfCancelled($bookId, $response['Data']);

        return in_array($outcome, ['recorded', 'already_recorded'], true);
    }
}
