<?php

namespace Tests\Unit\Services\DY365;

use App\Services\DY365\DyRetryPolicy as P;
use PHPUnit\Framework\TestCase;

class DyRetryPolicyTest extends TestCase
{
    private const READ_TIMEOUT = 'cURL error 28: Operation timed out after 60001 milliseconds with 0 bytes received';
    private const CONNECT_FAIL = 'cURL error 7: Failed to connect to dy.example port 443 after 12 ms: Connection refused';
    private const CONNECT_TIMEOUT = 'cURL error 28: Connection timed out after 5001 milliseconds';

    /** @test */
    public function reads_keep_retrying_exactly_as_before(): void
    {
        $this->assertTrue(P::shouldRetry(true, true, self::READ_TIMEOUT, null));
        $this->assertTrue(P::shouldRetry(true, true, self::CONNECT_FAIL, null));
        foreach ([429, 500, 502, 503, 504] as $status) {
            $this->assertTrue(P::shouldRetry(true, false, null, $status), "status {$status}");
        }
        $this->assertFalse(P::shouldRetry(true, false, null, 400));
        $this->assertFalse(P::shouldRetry(true, false, null, 404));
    }

    /** @test */
    public function a_write_is_not_resent_after_a_read_timeout(): void
    {
        // DY may have processed it and only the answer was lost.
        $this->assertFalse(P::shouldRetry(false, true, self::READ_TIMEOUT, null));
        $this->assertFalse(P::shouldRetry(false, true, 'cURL error 56: Recv failure: Connection reset by peer', null));
        $this->assertFalse(P::shouldRetry(false, true, 'cURL error 52: Empty reply from server', null));
    }

    /** @test */
    public function a_write_is_retried_when_dy_certainly_never_received_it(): void
    {
        $this->assertTrue(P::shouldRetry(false, true, self::CONNECT_FAIL, null));
        $this->assertTrue(P::shouldRetry(false, true, self::CONNECT_TIMEOUT, null));
        $this->assertTrue(P::shouldRetry(false, true, 'cURL error 6: Could not resolve host: dy.example', null));
    }

    /** @test */
    public function a_write_is_retried_only_for_statuses_that_mean_refused_up_front(): void
    {
        $this->assertTrue(P::shouldRetry(false, false, null, 429));
        $this->assertTrue(P::shouldRetry(false, false, null, 503));

        foreach ([500, 502, 504, 400, 404] as $status) {
            $this->assertFalse(P::shouldRetry(false, false, null, $status), "status {$status}");
        }
    }

    /** @test */
    public function nothing_known_means_no_retry(): void
    {
        $this->assertFalse(P::shouldRetry(true, false, null, null));
        $this->assertFalse(P::shouldRetry(false, false, null, null));
    }
}
