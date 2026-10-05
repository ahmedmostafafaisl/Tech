<?php

namespace App\Services\Serials;

/**
 * Which locally recorded serials still count as "used" — pure decision logic, so it can be
 * tested without a database.
 *
 * A serial recorded on one of the technician's appointment transactions is used when EITHER
 *   - the transaction was created yesterday-through-today (in progress, or just finished) —
 *     the window main-warehouse has always used; OR
 *   - its appointment is COMPLETED and the transaction is at most $completedDays old.
 *
 * The second rule exists because DY keeps listing a consumed serial on the technician's
 * warehouse for days after the appointment completes (seen: 6+ days). With only the 2-day
 * window those serials re-appeared as "available". It is bounded (default 90 days) so a
 * serial that genuinely returns to stock eventually stops being hidden, and it applies only
 * to COMPLETED appointments so an abandoned, never-completed reservation can't hide a serial
 * for months.
 */
final class UsedSerialWindow
{
    public const DEFAULT_COMPLETED_DAYS = 90;

    /** Start of the "recent" window: yesterday 00:00 in $now's timezone. */
    public static function recentStart(\DateTimeInterface $now): \DateTimeImmutable
    {
        return (new \DateTimeImmutable($now->format('Y-m-d') . ' 00:00:00', $now->getTimezone()))->modify('-1 day');
    }

    /** The earliest created_at the query has to fetch to apply both rules. */
    public static function fetchStart(\DateTimeInterface $now, int $completedDays): \DateTimeImmutable
    {
        $recent = self::recentStart($now);

        if ($completedDays <= 0) {
            return $recent;
        }

        $completed = \DateTimeImmutable::createFromInterface($now)->modify("-{$completedDays} days")->setTime(0, 0);

        return $completed < $recent ? $completed : $recent;
    }

    public static function isUsed(
        string|\DateTimeInterface $createdAt,
        bool $appointmentCompleted,
        \DateTimeInterface $now,
        int $completedDays
    ): bool {
        $created = $createdAt instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($createdAt)
            : new \DateTimeImmutable($createdAt, $now->getTimezone());

        if ($created >= self::recentStart($now)) {
            return true;
        }

        if (! $appointmentCompleted || $completedDays <= 0) {
            return false;
        }

        return $created >= \DateTimeImmutable::createFromInterface($now)->modify("-{$completedDays} days");
    }

    /**
     * @param  iterable<object>  $rows  each with item_number, serial, tx_created_at, tx_completed
     * @return array<string, array<int, string>>  item number (lowercase) => cleaned serials, used ones only
     */
    public static function groupUsed(iterable $rows, \DateTimeInterface $now, int $completedDays): array
    {
        $out = [];

        foreach ($rows as $row) {
            if (self::isUsed($row->tx_created_at, (bool) $row->tx_completed, $now, $completedDays)) {
                self::push($out, $row);
            }
        }

        return self::uniqueLists($out);
    }

    /**
     * One appointment's own serials, whatever their age.
     *
     * @param  iterable<object>  $rows  each with item_number, serial
     * @return array<string, array<int, string>>
     */
    public static function groupAll(iterable $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            self::push($out, $row);
        }

        return self::uniqueLists($out);
    }

    /** @param array<string, array<int, string>> $a  @param array<string, array<int, string>> $b */
    public static function mergeByItem(array $a, array $b): array
    {
        foreach ($b as $item => $serials) {
            $a[$item] = array_values(array_unique(array_merge($a[$item] ?? [], $serials)));
        }

        return $a;
    }

    private static function push(array &$out, object $row): void
    {
        $serial = UsedSerialFilter::clean($row->serial);

        if ($serial !== '') {
            $out[strtolower(trim((string) $row->item_number))][] = $serial;
        }
    }

    private static function uniqueLists(array $out): array
    {
        return array_map(fn(array $list) => array_values(array_unique($list)), $out);
    }
}
