<?php

namespace App\Services\Serials;

use App\Models\TransmittedSerial;
use App\Services\DY365\DyService;
use Illuminate\Support\Collection;

/**
 * The single, shared definition of "this serial is permanently unavailable elsewhere" — used by
 * NewDirectIntegrationController (singleAppointmentByBookId, mainWarehouse) for what available_serials
 * shows, and by SendPaymentLinksRequest / NewCompleteAppointmentRequest for what they accept, so a serial
 * can never be rejected at submission time that the appointment API was still showing as selectable.
 *
 * A serial is reserved (unavailable elsewhere) only when ALL of these hold:
 *  1. It's confirmed sent to DY365 under a different appointment (transmitted_serials, populated at the
 *     moment dy_body is written — see TransmittedSerialIndexer — never a live dy_body parse here).
 *  2. That OTHER appointment has no ChangeRequest at all. A change/cancel request means the original
 *     transmission may be voided, so the serial must not stay permanently locked away from every other
 *     appointment on its account.
 *  3. That OTHER appointment's CURRENT live DY365 status is not Canceled/Cancelled. A return/refund can
 *     cancel an appointment well after it was completed and transmitted, with no ChangeRequest ever
 *     created for it (a ChangeRequest is a customer-initiated reschedule/cancel ask, not what DY365 itself
 *     reports after a return) — rule 2 alone misses this case entirely.
 *
 * No time window anywhere in this check — deliberately unlike the old reservedSerialsByItem() /
 * UsedSerialWindow "recent window" rule this replaces at every call site: once a serial is confirmed sent
 * and its appointment has no change request and is not cancelled, it is reserved for as long as that
 * remains true, not for some number of days.
 */
class PermanentlyTransmittedSerials
{
    /** DY365 has used both spellings in practice — checked for elsewhere in this codebase too. */
    private const CANCELLED_STATUSES = ['Canceled', 'Cancelled'];

    /**
     * A live DY365 call per distinct candidate book_id (cached — see liveCancelledBookIds()) is only
     * acceptable because it's bounded to a small number of candidates: every DISTINCT appointment that has
     * EVER transmitted one of the requested items' serials, not every appointment in the system. This cap
     * is a defensive backstop against a pathological case (one item transmitted under an unusually large
     * number of different appointments) turning into an unbounded number of outbound calls on one request;
     * candidates beyond it are left reserved rather than risk a slow or hung response.
     */
    private const MAX_LIVE_STATUS_CHECKS = 20;

    /**
     * @param  string|null  $exceptBookId  never reserve a serial against its own appointment; null when
     *                                     there is no single "current" appointment (e.g. mainWarehouse)
     * @param  array<int, string>|null  $itemNumbers  restrict to these items; null for no restriction, [] to short-circuit to empty
     * @return array<string, array<string, string>> lower-cased item_number => [serial => reserving book_id]
     */
    public static function reservedBookIdsByItem(?string $exceptBookId = null, ?array $itemNumbers = null): array
    {
        if ($itemNumbers !== null && $itemNumbers === []) {
            return [];
        }

        // Grouping below normalizes every item_number to lower-case, so the final result is correct
        // regardless of casing either way — this is purely about what the database is asked to filter by.
        $normalizedItemNumbers = $itemNumbers !== null
            ? collect($itemNumbers)->map(fn($i) => strtolower(trim((string) $i)))->all()
            : null;

        // The SQL-level item_number filter was previously removed entirely (moved to PHP only), to work

        $itemNumberCandidates = $itemNumbers !== null
            ? collect($itemNumbers)
            ->flatMap(fn($i) => [(string) $i, strtolower(trim((string) $i)), strtoupper(trim((string) $i))])
            ->unique()
            ->values()
            ->all()
            : null;

        // Previously fetched every book_id that has EVER had a ChangeRequest into a PHP array, then used
        // whereNotIn() with it — binding one SQL parameter per row. In production that list grew well past
        // MySQL's prepared-statement placeholder limit (1390: "too many placeholders"), breaking this query
        // outright on every call. whereNotExists() checks the same condition as a subquery instead, with no
        // array binding at all, so it stays correct regardless of how large change_requests grows.
        $rows = TransmittedSerial::query()
            ->leftJoin(
                'change_requests as cr',
                'cr.book_id',
                '=',
                'transmitted_serials.book_id'
            )
            ->select(
                'transmitted_serials.item_number',
                'transmitted_serials.serial',
                'transmitted_serials.book_id'
            )
            ->when(
                $exceptBookId !== null,
                fn($q) => $q->where('transmitted_serials.book_id', '!=', $exceptBookId)
            )
            ->when(
                $itemNumberCandidates !== null,
                fn($q) => $q->whereIn('transmitted_serials.item_number', $itemNumberCandidates)
            )
            ->whereNull('cr.book_id')
            ->get();

        $cancelledBookIds = self::liveCancelledBookIds($rows->pluck('book_id')->unique()->values());

        if ($cancelledBookIds->isNotEmpty()) {
            $rows = $rows->reject(fn($row) => $cancelledBookIds->contains($row->book_id))->values();
        }

        $grouped = collect($rows->groupBy(fn($row) => strtolower(trim((string) $row->item_number)))->all());

        return $grouped
            ->when($normalizedItemNumbers !== null, fn($g) => $g->only($normalizedItemNumbers))
            ->map(fn($rows) => collect($rows)->mapWithKeys(fn($row) => [self::cleanSerial($row->serial) => $row->book_id])->all())
            ->all();
    }

    /**
     * Which of these book_ids are now Canceled/Cancelled in DY365, checked live (getAppointmentByBookIdNew
     * already caches each lookup for 60s with a circuit breaker, so repeated calls across nearby requests
     * for the same book_id are cheap). A lookup that fails or is inconclusive is NOT treated as cancelled —
     * when DY365 can't confirm, the serial stays on the safe side (still reserved) rather than risk
     * releasing one that's actually still a valid, completed sale.
     */
    private static function liveCancelledBookIds(Collection $bookIds): Collection
    {
        if ($bookIds->isEmpty()) {
            return collect();
        }

        $dyService = app(DyService::class);

        return $bookIds
            ->take(self::MAX_LIVE_STATUS_CHECKS)
            ->filter(function ($bookId) use ($dyService) {
                try {
                    $response = $dyService->getAppointmentByBookIdNew((string) $bookId, 60);
                } catch (\Throwable $e) {
                    return false;
                }

                if (! ($response['Status'] ?? false) || ! isset($response['Data'])) {
                    return false;
                }

                return in_array($response['Data']['Status'] ?? null, self::CANCELLED_STATUSES, true);
            })
            ->values();
    }

    /**
     * Strips non-breaking spaces and other invisible/zero-width characters DY365 sometimes includes in a
     * serial value. Every caller comparing a serial against this service's output should clean its own
     * side the same way (e.g. a serial typed or scanned by a technician) — this only normalizes what's
     * already stored here.
     */
    public static function cleanSerial($serial): string
    {
        return preg_replace('/[\pZ\pC\x{00A0}\x{200B}\x{FEFF}]+/u', '', (string) $serial);
    }

    /** @return array<string, array<int, string>> lower-cased item_number => serial list (no book_id detail) */
    public static function reservedSerialsByItem(?string $exceptBookId = null, ?array $itemNumbers = null): array
    {
        return collect(self::reservedBookIdsByItem($exceptBookId, $itemNumbers))
            ->map(fn(array $serialToBookId) => array_keys($serialToBookId))
            ->all();
    }
}
