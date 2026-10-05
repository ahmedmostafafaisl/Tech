<?php

namespace Tests\Unit\Services\DY365;

use App\Services\DY365\DyRequestBudget;
use PHPUnit\Framework\TestCase;

class DyRequestBudgetTest extends TestCase
{
    protected function tearDown(): void
    {
        DyRequestBudget::clear();
        parent::tearDown();
    }

    /** @test */
    public function without_a_budget_nothing_changes(): void
    {
        $this->assertFalse(DyRequestBudget::active());
        $this->assertNull(DyRequestBudget::remaining());
        $this->assertFalse(DyRequestBudget::exhausted());
        $this->assertSame([60, 2], DyRequestBudget::plan(60, 2));
        $this->assertSame([500, 3], DyRequestBudget::plan(500, 3));
    }

    /** @test */
    public function timeout_is_capped_to_what_is_left(): void
    {
        $this->assertSame([40, 1], DyRequestBudget::plan(60, 2, 40.0));
        $this->assertSame([15, 1], DyRequestBudget::plan(20, 2, 15.9)); // floors
    }

    /** @test */
    public function a_retry_is_kept_only_when_a_second_attempt_fits(): void
    {
        $this->assertSame([60, 2], DyRequestBudget::plan(60, 2, 200.0));  // 200 >= 60*2+2
        $this->assertSame([60, 1], DyRequestBudget::plan(60, 2, 100.0));  // 100 <  122
        $this->assertSame([500, 3], DyRequestBudget::plan(500, 3, 1400.0));
    }

    /** @test */
    public function the_smallest_usable_budget_is_one_second_one_attempt(): void
    {
        $this->assertSame([1, 1], DyRequestBudget::plan(60, 2, 1.0));
    }

    /** @test */
    public function an_exhausted_budget_refuses_to_start_a_call(): void
    {
        $this->expectException(\RuntimeException::class);
        DyRequestBudget::plan(60, 2, 0.5);
    }

    /** @test */
    public function begin_and_clear_manage_the_active_budget(): void
    {
        DyRequestBudget::begin(5);

        $this->assertTrue(DyRequestBudget::active());
        $this->assertTrue(DyRequestBudget::remaining() > 0 && DyRequestBudget::remaining() <= 5);
        $this->assertFalse(DyRequestBudget::exhausted());

        DyRequestBudget::clear();

        $this->assertFalse(DyRequestBudget::active());
        $this->assertNull(DyRequestBudget::remaining());
    }

    /** @test */
    public function an_expired_budget_reads_as_exhausted_and_blocks_new_calls(): void
    {
        DyRequestBudget::begin(0.2);

        $this->assertTrue(DyRequestBudget::exhausted());

        $this->expectException(\RuntimeException::class);
        DyRequestBudget::plan(60, 2);
    }
}
