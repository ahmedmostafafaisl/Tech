<?php

namespace Tests\Unit\Services\Serials;

use App\Services\Serials\UsedSerialFilter as F;
use PHPUnit\Framework\TestCase;

class UsedSerialFilterTest extends TestCase
{
    private function row(string $serial, float $qty = 1.0): array
    {
        return ['serial' => $serial, 'quantity' => $qty];
    }

    /** @test */
    public function clean_strips_every_kind_of_padding_but_keeps_the_serial_intact(): void
    {
        $this->assertSame('012604002185', F::clean("  012604002185\u{00A0}"));
        $this->assertSame('KEP0426NQ00241', F::clean("\u{FEFF}KEP0426NQ00241\u{200B}"));
        $this->assertSame('SILVER-QLA5772', F::clean("SILVER-QLA5772\n\t "));
        $this->assertSame('abAB09', F::clean('abAB09'));            // case and digits untouched
        $this->assertSame('', F::clean(null));
    }

    /** @test */
    public function used_serials_are_removed_and_the_rest_keep_order_and_fields(): void
    {
        $available = [$this->row('A1', 1), $this->row('B2', 2), $this->row('C3', 3)];

        $out = F::hide($available, ['B2']);

        $this->assertSame([$this->row('A1', 1), $this->row('C3', 3)], $out);
        $this->assertSame([0, 1], array_keys($out));              // re-indexed → serialises as a JSON array
    }

    /** @test */
    public function formatting_differences_do_not_defeat_the_match(): void
    {
        $out = F::hide([$this->row("012604002185\u{00A0}"), $this->row('102604001882')], ['  012604002185 ']);

        $this->assertSame(['102604001882'], array_column($out, 'serial'));
    }

    /** @test */
    public function nothing_used_returns_the_list_unchanged(): void
    {
        $available = [$this->row('A1'), $this->row('B2')];

        $this->assertSame($available, F::hide($available, []));
        $this->assertSame($available, F::hide($available, ['', '   ']));   // blanks never match anything
    }

    /** @test */
    public function matching_is_exact_not_numeric_and_not_case_insensitive(): void
    {
        $out = F::hide([$this->row('123'), $this->row('0123'), $this->row('abc')], ['0123', 'ABC']);

        $this->assertSame(['123', 'abc'], array_column($out, 'serial'));
    }

    /** @test */
    public function a_row_without_a_serial_key_is_kept_and_does_not_crash(): void
    {
        $out = F::hide([['quantity' => 1], $this->row('A1')], ['A1']);

        $this->assertSame([['quantity' => 1]], $out);
    }
}
