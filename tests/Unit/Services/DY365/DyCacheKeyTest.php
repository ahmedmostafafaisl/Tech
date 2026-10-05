<?php

namespace Tests\Unit\Services\DY365;

use App\Services\DY365\DyCacheKey;
use PHPUnit\Framework\TestCase;

class DyCacheKeyTest extends TestCase
{
    private const PROD = 'https://hamat-prod.operations.eu.dynamics.com';
    private const UAT  = 'https://hamat-uat.sandbox.operations.eu.dynamics.com';

    /** @test */
    public function different_environments_never_share_a_key(): void
    {
        foreach (['oauth:access_token', 'breaker:getWarehouseStock', 'getAppointmentByBookId:abc123'] as $suffix) {
            $this->assertFalse(DyCacheKey::make(self::PROD, $suffix) === DyCacheKey::make(self::UAT, $suffix), $suffix);
        }
    }

    /** @test */
    public function the_same_environment_always_gets_the_same_key(): void
    {
        $this->assertSame(
            DyCacheKey::make(self::PROD, 'oauth:access_token'),
            DyCacheKey::make(self::PROD, 'oauth:access_token')
        );
    }

    /** @test */
    public function a_trailing_slash_or_different_case_does_not_split_the_cache(): void
    {
        $a = DyCacheKey::make(self::PROD, 'x');

        $this->assertSame($a, DyCacheKey::make(self::PROD . '/', 'x'));
        $this->assertSame($a, DyCacheKey::make('  ' . strtoupper(self::PROD) . '  ', 'x'));
    }

    /** @test */
    public function the_key_keeps_the_dy_prefix_and_the_suffix(): void
    {
        $key = DyCacheKey::make(self::PROD, 'breaker:getWarehouseStock');

        $this->assertSame(1, preg_match('/^dy:[0-9a-f]{8}:breaker:getWarehouseStock$/', $key));
    }

    /** @test */
    public function different_suffixes_in_one_environment_stay_distinct(): void
    {
        $this->assertFalse(DyCacheKey::make(self::PROD, 'a') === DyCacheKey::make(self::PROD, 'b'));
    }
}
