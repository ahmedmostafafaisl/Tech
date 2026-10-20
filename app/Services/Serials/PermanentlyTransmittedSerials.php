<?php

namespace App\Services\Serials;

use App\Models\TransmittedSerial;

/**
 * The single, shared definition of "this serial is permanently unavailable elsewhere" — used by
 * NewDirectIntegrationController (singleAppointmentByBookId, mainWarehouse) for what available_serials
 * shows, and by SendPaymentLinksRequest / NewCompleteAppointmentRequest for what they accept, so a serial
 * can never be rejected at submission time that the appointment API was still showing as selectable.
 *
 * A serial is reserved (unavailable elsewhere) only when BOTH of these hold:
 *  1. It's confirmed sent to DY365 under a different appointment (transmitted_serials, populated at the
 *     moment dy_body is written — see TransmittedSerialIndexer — never a live dy_body parse here).
 *  2. That OTHER appointment has no ChangeRequest at all. A change/cancel request means the original
 *     transmission may be voided, so the serial must not stay permanently locked away from every other
 *     appointment on its account.
 *
 * Deliberately no live DY365 status check here. A return/refund can cancel an appointment well after it was
 * completed and transmitted, with no ChangeRequest ever created for it on its own — but rather than this
 * service checking DY365's live status itself (which it used to do, at a real performance cost: one DY365
 * call per distinct candidate appointment, on every single lookup), CancellationChangeRequestRecorder
 * detects that cancellation once — wherever an appointment's current status is already being fetched for
 * another reason (singleAppointmentByBookId, mainWarehouse) — and records it as a ChangeRequest the first
 * time it's seen. Every lookup after that is covered by rule 2 above with no DY365 call of its own.
 *
 * No time window anywhere in this check either — deliberately unlike the old reservedSerialsByItem() /
 * UsedSerialWindow "recent window" rule this replaces at every call site: once a serial is confirmed sent
 * and its appointment has no change request, it is reserved for as long as that remains true, not for some
 * number of days.
 */
class PermanentlyTransmittedSerials
{
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
        // around what looked like a case-sensitivity mismatch — but that was an artifact of SQLite (used in
        // this codebase's tests), which compares strings case-sensitively by default; MySQL's standard
        // collation (utf8mb4_unicode_ci or similar) already matches case-insensitively. Removing the filter
        // meant every call scanned the ENTIRE transmitted_serials table with no database-side filtering at
        // all, which caused serious load in production. Restored here, matching both the original casing
        // and a normalized variant of each item number (so this stays correct even if a column's collation
        // ever turns out to be case-sensitive), which lets the (item_number, serial) index actually be used
        // and cuts the row count down before the join below ever has to run.
        $itemNumberCandidates = $itemNumbers !== null
            ? collect($itemNumbers)
            ->flatMap(fn($i) => [(string) $i, strtolower(trim((string) $i)), strtoupper(trim((string) $i))])
            ->unique()
            ->values()
            ->all()
            : null;

        // A LEFT JOIN against change_requests, keeping only rows with no match (cr.book_id IS NULL) — an
        // earlier version fetched every book_id that has EVER had a ChangeRequest into a PHP array and used
        // whereNotIn() with it, binding one SQL parameter per row; in production that list grew well past
        // MySQL's prepared-statement placeholder limit (1390: "too many placeholders"), breaking this query
        // outright on every call. This join avoids that array binding entirely, so it stays correct
        // regardless of how large change_requests grows. A book_id with more than one ChangeRequest row
        // produces one joined row per match here, all excluded by the IS NULL check below — none of them
        // slip through, so this is safe even though change_requests.book_id isn't unique.
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

        $grouped = collect($rows->groupBy(fn($row) => strtolower(trim((string) $row->item_number)))->all());

        return $grouped
            ->when($normalizedItemNumbers !== null, fn($g) => $g->only($normalizedItemNumbers))
            ->map(fn($rows) => collect($rows)->mapWithKeys(fn($row) => [self::cleanSerial($row->serial) => $row->book_id])->all())
            ->all();
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
