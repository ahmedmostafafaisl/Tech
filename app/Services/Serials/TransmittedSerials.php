<?php

namespace App\Services\Serials;

/**
 * Which serials a COMPLETED appointment's own dy_body says were actually sent to DY365 — the one
 * authoritative record of what was transmitted, independent of whatever got recorded locally in
 * appointment_transaction_serials along the way.
 *
 * A product can be added with one serial, removed, and re-added with another; only the final one is
 * ever sent to DY365, in dy_body's SalesLinesSerial. But nothing guarantees the abandoned serial's row
 * in appointment_transaction_serials gets cleaned up when that happens — if it doesn't, that orphaned
 * row survives and, left unchecked, wrongly suppresses the abandoned serial forever (serialsOfAppointmentByItem
 * previously treated every row ever recorded under a completed appointment as "sent", with no way to
 * tell a transmitted serial from a replaced one). Filtering against dy_body is what tells them apart.
 */
final class TransmittedSerials
{
    /**
     * The set of cleaned serial numbers dy_body's SalesLinesSerial actually lists, or null when that
     * can't be determined — dy_body missing, not valid JSON, or without a SalesLinesSerial array at
     * all. null means "unknown", not "none": it is the signal to fail safe (see filter()).
     *
     * @return array<int, string>|null
     */
    public static function extract(?string $dyBody): ?array
    {
        if ($dyBody === null || trim($dyBody) === '') {
            return null;
        }

        $decoded = json_decode($dyBody, true);

        if (! is_array($decoded)) {
            return null;
        }

        $lines = $decoded['_contract']['SalesLinesSerial'] ?? null;

        if (! is_array($lines)) {
            return null;
        }

        $serials = [];

        foreach ($lines as $line) {
            foreach ((array) ($line['SerialNumber'] ?? []) as $entry) {
                $serial = UsedSerialFilter::clean(is_array($entry) ? ($entry['Serial'] ?? '') : '');

                if ($serial !== '') {
                    $serials[$serial] = true;
                }
            }
        }

        return array_keys($serials);
    }

    /**
     * Keeps only the rows whose serial was actually transmitted.
     *
     * $transmitted === null ("unknown": dy_body missing/unparseable) fails safe by returning every row
     * unfiltered — the behaviour before this filter existed — rather than risking a genuinely-sent
     * serial reappearing as available just because its appointment's dy_body couldn't be read.
     *
     * @param  iterable<object>  $rows  each with a `serial` property
     * @param  array<int, string>|null  $transmitted
     * @return array<int, object>
     */
    public static function filter(iterable $rows, ?array $transmitted): array
    {
        $rows = $rows instanceof \Traversable ? iterator_to_array($rows) : (array) $rows;

        if ($transmitted === null) {
            return $rows;
        }

        $allowed = array_fill_keys($transmitted, true);

        return array_values(array_filter(
            $rows,
            fn($row) => isset($allowed[UsedSerialFilter::clean($row->serial ?? '')])
        ));
    }
}
