<?php

namespace Tests\Unit\Services\DY365;

use App\Services\DY365\DyFailureKind as K;
use App\Services\DY365\DyRequestFailed;
use PHPUnit\Framework\TestCase;

class DyFailureKindTest extends TestCase
{
    /** @test */
    public function answers_about_one_request_do_not_trip_the_breaker(): void
    {
        foreach ([400, 404, 409, 422] as $status) {
            $this->assertFalse(K::countsAgainstBreaker($status, false, 'record not found'), "status {$status}");
        }
    }

    /** @test */
    public function signs_that_dy_or_the_connection_is_unhealthy_do(): void
    {
        foreach ([429, 408, 500, 502, 503, 504] as $status) {
            $this->assertTrue(K::countsAgainstBreaker($status, false, 'x'), "status {$status}");
        }
        $this->assertTrue(K::countsAgainstBreaker(null, true, 'cURL error 28: Operation timed out'));
        $this->assertTrue(K::countsAgainstBreaker(null, true, 'cURL error 7: Connection refused'));
    }

    /** @test */
    public function systemic_auth_failures_count_because_every_call_will_fail(): void
    {
        $this->assertTrue(K::countsAgainstBreaker(401, false, 'x'));
        $this->assertTrue(K::countsAgainstBreaker(403, false, 'x'));
        $this->assertTrue(K::countsAgainstBreaker(null, false, 'Missing access token'));
        $this->assertTrue(K::countsAgainstBreaker(null, false, 'Token refresh failed after 401'));
    }

    /** @test */
    public function our_own_time_budget_running_out_never_counts(): void
    {
        $this->assertFalse(K::countsAgainstBreaker(null, false, 'DY request budget exhausted'));
        $this->assertFalse(K::countsAgainstBreaker(null, true, 'DY request budget exhausted'));
    }

    /** @test */
    public function an_unknown_failure_counts_exactly_as_before(): void
    {
        $this->assertTrue(K::countsAgainstBreaker(null, false, 'something unexpected'));
        $this->assertTrue(K::countsAgainstBreaker(null, false, null));
    }

    /** @test */
    public function dy_request_failed_keeps_the_status_and_still_is_a_runtime_exception(): void
    {
        $e = new DyRequestFailed('not found', 404);

        $this->assertSame(404, $e->status);
        $this->assertSame('not found', $e->getMessage());
        $this->assertTrue($e instanceof \RuntimeException);
        $this->assertNull((new DyRequestFailed('x'))->status);
    }
}
