<?php

namespace App\Services\Serials;

use App\Models\ChangeRequest;
use App\Models\TransmittedSerial;
use Illuminate\Support\Collection;

/**
 * The single, shared definition of "this serial is permanently unavailable elsewhere" — used by
 * NewDirectIntegrationController (singleAppointmentByBookId, mainWarehouse) for what available_serials
 * shows, and by SendPaymentLinksRequest / NewCompleteAppointmentRequest for what they accept, so a serial
 * can never be rejected at submission time that the appointment API was still showing as selectable.
 *
 * A serial is reserved (unavailable elsewhere) only when BOTH hold:
 *  1. It's confirmed sent to DY365 under a different appointment (transmitted_serials, populated at the
 *     moment dy_body is written — see TransmittedSerialIndexer — never a live dy_body parse here).
 *  2. That OTHER appointment has no ChangeRequest at all. A change/cancel request means the original
 *     transmission may be voided, so the serial must not stay permanently locked away from every other
 *     appointment on its account.
 *
 * No time window anywhere in this check — deliberately unlike the old reservedSerialsByItem() /
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

        $excludedBookIds = ChangeRequest::query()
            ->whereNotNull('book_id')
            ->pluck('book_id')
            ->unique()
            ->values();

        // item_number filtering happens in PHP, not SQL: item_number case varies between callers (DY365
        // responses, request bodies) and what's stored here, and the grouping below already normalizes to
        // lower-case — an SQL whereIn on the raw, differently-cased values would silently drop matching
        // rows instead of finding them.
        $normalizedItemNumbers = $itemNumbers !== null
            ? collect($itemNumbers)->map(fn($i) => strtolower(trim((string) $i)))->all()
            : null;

        $rows = TransmittedSerial::query()
            ->select('item_number', 'serial', 'book_id')
            ->when($exceptBookId !== null, fn($q) => $q->where('book_id', '!=', $exceptBookId))
            ->when($excludedBookIds->isNotEmpty(), fn($q) => $q->whereNotIn('book_id', $excludedBookIds))
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
