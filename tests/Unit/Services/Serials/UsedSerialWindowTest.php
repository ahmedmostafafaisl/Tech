<?php

namespace Tests\Unit\Services\Serials;

use App\Services\Serials\UsedSerialWindow as W;
use PHPUnit\Framework\TestCase;

class UsedSerialWindowTest extends TestCase
{
    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-10-05 15:30:00', new \DateTimeZone('Asia/Riyadh'));
    }

    private function row(string $item, string $serial, string $createdAt = '2026-10-05 10:00:00', bool $completed = false): object
    {
        return (object) ['item_number' => $item, 'serial' => $serial, 'tx_created_at' => $createdAt, 'tx_completed' => $completed ? 1 : 0];
    }

    /** @test */
    public function the_recent_window_is_yesterday_midnight_through_today_exactly_as_before(): void
    {
        $this->assertSame('2026-10-04 00:00:00', W::recentStart($this->now())->format('Y-m-d H:i:s'));

        $this->assertTrue(W::isUsed('2026-10-04 00:00:00', false, $this->now(), 90));   // boundary: included
        $this->assertTrue(W::isUsed('2026-10-05 09:00:00', false, $this->now(), 90));
        $this->assertFalse(W::isUsed('2026-10-03 23:59:59', false, $this->now(), 90));  // one second earlier: out
    }

    /** @test */
    public function the_reported_case_a_completed_appointment_six_days_ago_is_still_used(): void
    {
        // book APP001493118: transaction created 2026-09-29 22:44:09, appointment completed
        $this->assertTrue(W::isUsed('2026-09-29 22:44:09', true, $this->now(), 90));
        // …whereas the old 2-day rule (completedDays = 0) lets it through, which is the bug
        $this->assertFalse(W::isUsed('2026-09-29 22:44:09', true, $this->now(), 0));
    }

    /** @test */
    public function an_old_reservation_that_never_completed_does_not_hide_a_serial(): void
    {
        $this->assertFalse(W::isUsed('2026-09-29 22:44:09', false, $this->now(), 90));
    }

    /** @test */
    public function completed_serials_stop_being_hidden_after_the_window(): void
    {
        $now = $this->now();

        $this->assertTrue(W::isUsed($now->modify('-89 days')->format('Y-m-d H:i:s'), true, $now, 90));
        $this->assertFalse(W::isUsed($now->modify('-91 days')->format('Y-m-d H:i:s'), true, $now, 90));
        $this->assertTrue(W::isUsed($now->modify('-40 days')->format('Y-m-d H:i:s'), true, $now, 60));
        $this->assertFalse(W::isUsed($now->modify('-40 days')->format('Y-m-d H:i:s'), true, $now, 30));
    }

    /** @test */
    public function the_query_fetches_back_to_whichever_window_reaches_furthest(): void
    {
        $this->assertSame('2026-07-07 00:00:00', W::fetchStart($this->now(), 90)->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-04 00:00:00', W::fetchStart($this->now(), 0)->format('Y-m-d H:i:s'));   // feature off
        $this->assertSame('2026-10-04 00:00:00', W::fetchStart($this->now(), 1)->format('Y-m-d H:i:s'));   // never later than recent
    }

    /** @test */
    public function a_date_string_is_read_in_the_application_timezone_not_utc(): void
    {
        // 2026-10-04 00:00:00 Riyadh is the boundary; read as UTC it would be 3 hours later and still pass,
        // but 2026-10-03 22:00:00 must be OUT in Riyadh time (it would be 'before midnight' either way)
        $this->assertTrue(W::isUsed('2026-10-04 00:30:00', false, $this->now(), 90));
        $this->assertFalse(W::isUsed('2026-10-03 22:00:00', false, $this->now(), 90));
    }

    /** @test */
    public function grouping_keeps_only_used_rows_cleans_serials_and_lowercases_the_item(): void
    {
        $rows = [
            $this->row('NQ-APS-01', "AP7S0426CW2083\u{00A0}", '2026-09-29 22:46:03', true),   // completed, 6 days → used
            $this->row('NQ-APS-01', ' AP7S0426CW2084 ',       '2026-09-29 22:46:03', true),
            $this->row('nq-aps-01', 'AP7S0426CW9999',         '2026-09-29 22:46:03', false),  // never completed, old → not used
            $this->row('NQ-APS-01', 'AP7S0426CW5555',         '2026-10-05 10:00:00', false),  // recent → used
            $this->row('NQ-APS-01', 'AP7S0426CW2083',         '2026-10-05 11:00:00', false),  // duplicate of a used serial
            $this->row('hr167',     'X1',                      '2026-06-01 10:00:00', true),   // completed but > 90 days → not used
        ];

        $this->assertSame(
            ['nq-aps-01' => ['AP7S0426CW2083', 'AP7S0426CW2084', 'AP7S0426CW5555']],
            W::groupUsed($rows, $this->now(), 90)
        );
    }

    /** @test */
    public function blank_serials_are_ignored(): void
    {
        $this->assertSame([], W::groupAll([$this->row('a', "  \u{200B} ")]));
    }

    /** @test */
    public function an_appointments_own_serials_are_grouped_whatever_their_age(): void
    {
        $rows = [$this->row('NQ-APS-01', 'S1', '2025-01-01 10:00:00'), $this->row('NQ-APS-01', 'S2', '2025-01-01 10:00:00')];

        $this->assertSame(['nq-aps-01' => ['S1', 'S2']], W::groupAll($rows));
    }

    /** @test */
    public function merging_adds_serials_without_duplicates_and_keeps_other_items(): void
    {
        $merged = W::mergeByItem(['a' => ['1', '2']], ['a' => ['2', '3'], 'b' => ['9']]);

        $this->assertSame(['a' => ['1', '2', '3'], 'b' => ['9']], $merged);
        $this->assertSame(['a' => ['1']], W::mergeByItem([], ['a' => ['1']]));
        $this->assertSame(['a' => ['1']], W::mergeByItem(['a' => ['1']], []));
    }
}
