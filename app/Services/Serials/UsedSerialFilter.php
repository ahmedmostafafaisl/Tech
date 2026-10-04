<?php

namespace App\Services\Serials;

/**
 * Hides serials that are already used from a list of available serials.
 *
 * The same normalisation main-warehouse has always applied, in one place:
 * DY returns serials padded with regular, non-breaking and zero-width spaces,
 * so every comparison is done on the cleaned value. Matching is exact and
 * case-sensitive after cleaning (a serial is an identifier, not a label).
 */
final class UsedSerialFilter
{
    /** Strips regular / non-breaking / zero-width spaces, BOMs and control characters. */
    public static function clean(mixed $serial): string
    {
        return preg_replace('/[\pZ\pC\x{00A0}\x{200B}\x{FEFF}]+/u', '', (string) $serial) ?? '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $availableSerials  e.g. [['serial' => '0126…', 'quantity' => 1], …]
     * @param  array<int, string>                $usedSerials
     * @return array<int, array<string, mixed>>  same shape and order, used serials removed, re-indexed
     */
    public static function hide(array $availableSerials, array $usedSerials): array
    {
        $used = [];

        foreach ($usedSerials as $serial) {
            $clean = self::clean($serial);

            if ($clean !== '') {
                $used[$clean] = true;
            }
        }

        if ($used === []) {
            return array_values($availableSerials);
        }

        return array_values(array_filter(
            $availableSerials,
            fn($row) => ! isset($used[self::clean($row['serial'] ?? '')])
        ));
    }
}
