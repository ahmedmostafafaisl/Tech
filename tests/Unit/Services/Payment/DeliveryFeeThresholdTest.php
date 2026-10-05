<?php

namespace Tests\Unit\Services\Payment;

use App\Services\Payment\DeliveryFeeThreshold as T;
use PHPUnit\Framework\TestCase;

class DeliveryFeeThresholdTest extends TestCase
{
    private function goods(float $amount): array
    {
        return ['ItemNumber' => 'nq-ice-03', 'TotalAmount' => $amount, 'Quantity' => 1];
    }

    private function fee(float $amount = 35.0): array
    {
        return ['ItemNumber' => 'fes-transportation', 'TotalAmount' => $amount, 'Quantity' => 1];
    }

    /** @test */
    public function the_reported_appointment_is_valid(): void
    {
        // APP001503286: goods 499 + delivery fee 35 → TotalAmountSum 534.
        $dy = json_decode('{"Data":{"TotalAmountSum":534.0,"SalesLines":['
            . '{"ItemNumber":"nq-ice-03","TotalAmount":499.0,"UnitPrice":499.0,"Quantity":1.0},'
            . '{"ItemNumber":"fes-transportation","TotalAmount":35.0,"UnitPrice":35.0,"Quantity":1.0}]}}', true)['Data'];

        $this->assertSame(499.0, T::subtotal($dy['TotalAmountSum'], $dy['SalesLines']));
        $this->assertNull(T::violation($dy['TotalAmountSum'], $dy['SalesLines']));
    }

    /** @test */
    public function the_whole_465_to_499_dead_zone_now_has_a_valid_configuration(): void
    {
        foreach ([465.0, 480.0, 499.0, 499.5] as $goods) {
            $withFee = [$this->goods($goods), $this->fee()];

            $this->assertNull(T::violation($goods + 35.0, $withFee), "goods {$goods} + fee");
            $this->assertSame(T::MISSING, T::violation($goods, [$this->goods($goods)]), "goods {$goods} without fee");
        }
    }

    /** @test */
    public function at_or_above_500_a_delivery_fee_is_unexpected(): void
    {
        $this->assertNull(T::violation(500.0, [$this->goods(500.0)]));
        $this->assertSame(T::UNEXPECTED, T::violation(535.0, [$this->goods(500.0), $this->fee()]));
        $this->assertSame(T::UNEXPECTED, T::violation(555.0, [$this->goods(520.0), $this->fee()]));
    }

    /** @test */
    public function the_fee_is_taken_from_the_line_not_assumed_to_be_35(): void
    {
        // goods 470 + a 40 fee = 510. Subtracting an assumed 35 would give 475
        // (also fine), but subtracting nothing gives 510 → wrongly "unexpected".
        $this->assertSame(470.0, T::subtotal(510.0, [$this->goods(470.0), $this->fee(40.0)]));
        $this->assertNull(T::violation(510.0, [$this->goods(470.0), $this->fee(40.0)]));
    }

    /** @test */
    public function a_fee_line_without_total_amount_falls_back_to_unit_price_times_quantity(): void
    {
        $line = ['ItemNumber' => 'fes-transportation', 'UnitPrice' => 35.0, 'Quantity' => 2.0];

        $this->assertSame(430.0, T::subtotal(500.0, [$this->goods(430.0), $line]));
    }

    /** @test */
    public function the_tech_visit_exemption_is_unchanged(): void
    {
        $visit = ['ItemNumber' => 'fes-tech-visit', 'TotalAmount' => 35.0];

        $this->assertSame(480.0, T::subtotal(515.0, [$this->goods(480.0), $visit]));
        $this->assertNull(T::violation(515.0, [$this->goods(480.0), $visit]));   // low goods + visit: exempt
        $this->assertNull(T::violation(555.0, [$this->goods(520.0), $visit]));   // high goods, no fee: fine
    }

    /** @test */
    public function naqi_s00004_satisfies_the_low_value_requirement(): void
    {
        $naqi = ['ItemNumber' => 'naqi-s00004', 'TotalAmount' => 0.0];

        $this->assertNull(T::violation(100.0, [$this->goods(100.0), $naqi]));
    }

    /** @test */
    public function item_numbers_are_matched_case_and_whitespace_insensitively(): void
    {
        $fee = ['ItemNumber' => '  FES-Transportation ', 'TotalAmount' => 35.0];

        $this->assertNull(T::violation(534.0, [$this->goods(499.0), $fee]));
    }

    /** @test */
    public function cents_do_not_cause_float_noise_at_the_threshold(): void
    {
        $this->assertSame(499.1, T::subtotal(534.1, [$this->goods(499.1), $this->fee()]));
        $this->assertSame(500.0, T::subtotal(535.0, [$this->goods(500.0), $this->fee()]));
    }

    /** @test */
    public function any_iterable_of_lines_works(): void
    {
        $this->assertNull(T::violation(534.0, new \ArrayIterator([$this->goods(499.0), $this->fee()])));
    }
}
