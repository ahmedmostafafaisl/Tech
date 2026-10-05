<?php

namespace Tests\Unit\Services\ChangeRequest;

use App\Services\ChangeRequest\PendingChangeRequestGuard;
use PHPUnit\Framework\TestCase;

class PendingChangeRequestGuardTest extends TestCase
{
    /** @test */
    public function the_lock_key_is_per_appointment_and_request_type(): void
    {
        $a = PendingChangeRequestGuard::lockKey('APP001500654', 'SO-002367906', 1);

        $this->assertSame('change_request:inflight:APP001500654:SO-002367906:1', $a);
        $this->assertSame($a, PendingChangeRequestGuard::lockKey(' APP001500654 ', 'SO-002367906 ', 1)); // trims
        $this->assertFalse($a === PendingChangeRequestGuard::lockKey('APP001500654', 'SO-002367906', 0)); // cancel != reschedule
        $this->assertFalse($a === PendingChangeRequestGuard::lockKey('APP001500601', 'SO-002367906', 1)); // other appointment
    }

    /** @test */
    public function the_same_request_type_matches_whatever_form_the_validator_left_it_in(): void
    {
        $this->assertTrue(PendingChangeRequestGuard::sameRequest(['requestType' => 1], 1));
        $this->assertTrue(PendingChangeRequestGuard::sameRequest(['requestType' => '1'], 1));
        $this->assertTrue(PendingChangeRequestGuard::sameRequest(['requestType' => true], 1));
        $this->assertTrue(PendingChangeRequestGuard::sameRequest(['requestType' => '0'], 0));
        $this->assertTrue(PendingChangeRequestGuard::sameRequest(['requestType' => false], 0));
    }

    /** @test */
    public function a_different_request_type_is_never_treated_as_a_repeat(): void
    {
        $this->assertFalse(PendingChangeRequestGuard::sameRequest(['requestType' => 0], 1)); // cancel vs reschedule
        $this->assertFalse(PendingChangeRequestGuard::sameRequest(['requestType' => '1'], 0));
    }

    /** @test */
    public function an_unreadable_log_payload_is_never_treated_as_a_repeat(): void
    {
        $this->assertFalse(PendingChangeRequestGuard::sameRequest(null, 1));
        $this->assertFalse(PendingChangeRequestGuard::sameRequest([], 1));
        $this->assertFalse(PendingChangeRequestGuard::sameRequest(['notes' => 'x'], 1));
    }

    /** @test */
    public function the_mysql_lock_name_is_short_stable_and_per_request(): void
    {
        $a = PendingChangeRequestGuard::dbLockName(PendingChangeRequestGuard::lockKey('APP001500654', 'SO-002367906', 1));
        $b = PendingChangeRequestGuard::dbLockName(PendingChangeRequestGuard::lockKey('APP001500654', 'SO-002367906', 0));

        $this->assertTrue(strlen($a) <= 64);                       // MySQL's limit for lock names
        $this->assertSame($a, PendingChangeRequestGuard::dbLockName(PendingChangeRequestGuard::lockKey('APP001500654', 'SO-002367906', 1)));
        $this->assertFalse($a === $b);                              // cancel and reschedule never share a lock
    }
}
