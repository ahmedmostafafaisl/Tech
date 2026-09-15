<?php

namespace Tests\Unit\Services\Payment;

use App\Services\Payment\RequiredAmountCalculator;
use PHPUnit\Framework\TestCase;

class RequiredAmountCalculatorTest extends TestCase
{
    private RequiredAmountCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RequiredAmountCalculator();
    }

    /** @test */
    public function no_payment_and_no_balance_returns_full_amount(): void
    {
        $result = $this->calculator->calculate(159, 0, 0);
        $this->assertEquals(159, $result);
    }

    /** @test */
    public function partial_payment_reduces_the_amount(): void
    {
        $result = $this->calculator->calculate(159, 50, 0);
        $this->assertEquals(109, $result);
    }

    /** @test */
    public function payment_plus_balance_are_both_subtracted(): void
    {
        $result = $this->calculator->calculate(159, 50, 30);
        $this->assertEquals(79, $result);
    }

    /** @test */
    public function used_balance_greater_than_remaining_never_goes_negative(): void
    {
        // 159 - 100 = 59, then 59 - 100 = -41 -> must clamp to 0
        $result = $this->calculator->calculate(159, 100, 100);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function fully_paid_already_ignores_used_balance_entirely(): void
    {
        // 159 - 159 = 0, remaining is not > 0, so used_balance (50) must
        // NOT be applied at all — result stays exactly 0, not negative.
        $result = $this->calculator->calculate(159, 159, 50);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function used_balance_is_never_applied_when_remaining_is_zero_or_less(): void
    {
        $result = $this->calculator->calculate(100, 100, 0);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function zero_used_balance_has_no_effect(): void
    {
        $result = $this->calculator->calculate(200, 50, 0);
        $this->assertEquals(150, $result);
    }

    /** @test */
    public function result_is_always_a_float(): void
    {
        $result = $this->calculator->calculate(159, 50, 30);
        $this->assertIsFloat($result);
    }

    /** @test */
    public function used_balance_applied_is_zero_when_paid_amount_already_covers_it(): void
    {
        // 159 - 159 = 0, not > 0 -> balance never touched
        $result = $this->calculator->calculateUsedBalanceApplied(159, 159, 50);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function used_balance_applied_is_capped_at_what_remains_after_paid_amount(): void
    {
        // 159 - 100 = 59 remaining, but used_balance is 100 -> only 59 gets applied
        $result = $this->calculator->calculateUsedBalanceApplied(159, 100, 100);
        $this->assertEquals(59, $result);
    }

    /** @test */
    public function used_balance_applied_uses_the_full_balance_when_it_fits_within_remaining(): void
    {
        // 159 - 50 = 109 remaining, used_balance is 30 -> all 30 gets applied
        $result = $this->calculator->calculateUsedBalanceApplied(159, 50, 30);
        $this->assertEquals(30, $result);
    }
}
