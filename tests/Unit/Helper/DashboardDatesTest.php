<?php

namespace Tests\Unit\Helper;

use App\Helper\DashboardDates;
use PHPUnit\Framework\TestCase;

class DashboardDatesTest extends TestCase
{
    private string $originalTimezone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Riyadh'); // what config('app.timezone') makes Laravel do at boot
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    /** @test */
    public function laravels_utc_iso_string_becomes_the_riyadh_wall_clock_in_the_requested_format(): void
    {
        $this->assertSame('2026-10-05 10:11:12', DashboardDates::format('2026-10-05T07:11:12.000000Z'));
        $this->assertSame('2026-10-05 13:16:28', DashboardDates::format('2026-10-05T10:16:28.000000Z'));
    }

    /** @test */
    public function a_date_object_in_the_app_timezone_keeps_its_wall_clock(): void
    {
        $date = new \DateTimeImmutable('2026-10-05 07:11:12', new \DateTimeZone('Asia/Riyadh'));

        $this->assertSame('2026-10-05 07:11:12', DashboardDates::format($date));
        $this->assertSame('2026-10-05 07:11:12', DashboardDates::format(new \DateTime('2026-10-05 07:11:12')));
    }

    /** @test */
    public function formatting_twice_changes_nothing(): void
    {
        $once = DashboardDates::format('2026-10-05T07:11:12.000000Z');

        $this->assertSame($once, DashboardDates::format($once));
        $this->assertSame('2026-10-05 07:11:12', DashboardDates::format('2026-10-05 07:11:12'));
    }

    /** @test */
    public function the_result_follows_the_application_timezone(): void
    {
        date_default_timezone_set('UTC');

        $this->assertSame('2026-10-05 07:11:12', DashboardDates::format('2026-10-05T07:11:12.000000Z'));
    }

    /** @test */
    public function empty_and_unparseable_values_come_back_untouched(): void
    {
        $this->assertNull(DashboardDates::format(null));
        $this->assertSame('', DashboardDates::format(''));
        $this->assertSame('not a date', DashboardDates::format('not a date'));
        $this->assertSame(12345, DashboardDates::format(12345));
    }

    /** @test */
    public function only_created_at_and_updated_at_are_touched_at_every_depth(): void
    {
        $row = [
            'id'                  => 7,
            'status'              => 'paid',
            'created_at'          => '2026-10-05T07:11:12.000000Z',
            'updated_at'          => '2026-10-05T10:16:28.000000Z',
            'complete_v2_calling' => 'done:2026-10-05 10:15:00',
            'technician'          => [
                'id'                => 9,
                'created_at'        => '2026-01-01T05:00:00.000000Z',
                'updated_at'        => null,
                'email_verified_at' => '2026-01-01T05:00:00.000000Z',
            ],
            'payments' => [
                ['id' => 1, 'created_at' => '2026-10-05T07:12:00.000000Z', 'paid_at' => '2026-10-05T07:12:00.000000Z'],
            ],
            'created_at_note' => '2026-10-05T07:11:12.000000Z',
        ];

        $out = DashboardDates::timestamps($row);

        $this->assertSame('2026-10-05 10:11:12', $out['created_at']);
        $this->assertSame('2026-10-05 13:16:28', $out['updated_at']);
        $this->assertSame('2026-01-01 08:00:00', $out['technician']['created_at']);
        $this->assertNull($out['technician']['updated_at']);
        $this->assertSame('2026-10-05 10:12:00', $out['payments'][0]['created_at']);

        // everything else is exactly as it was
        $this->assertSame('2026-01-01T05:00:00.000000Z', $out['technician']['email_verified_at']);
        $this->assertSame('2026-10-05T07:12:00.000000Z', $out['payments'][0]['paid_at']);
        $this->assertSame('2026-10-05T07:11:12.000000Z', $out['created_at_note']);
        $this->assertSame('done:2026-10-05 10:15:00', $out['complete_v2_calling']);
        $this->assertSame([7, 'paid'], [$out['id'], $out['status']]);
        $this->assertSame(array_keys($row), array_keys($out));
    }
}
