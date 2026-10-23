<?php

use Invoice\Money;
use Invoice\TaxCalculator;
use PHPUnit\Framework\TestCase;

class TaxCalculatorTest extends TestCase
{
    private function lines(TaxCalculator $tax, string $shipTo): array
    {
        return array_map(fn (Money $m) => $m->paise, $tax->lines(Money::fromRupees(1000), $shipTo));
    }

    public function test_same_state_splits_into_cgst_and_sgst(): void
    {
        $this->assertSame(['CGST 9%' => 9000, 'SGST 9%' => 9000], $this->lines(new TaxCalculator('MH'), 'MH'));
    }

    public function test_other_state_pays_igst(): void
    {
        $this->assertSame(['IGST 18%' => 18000], $this->lines(new TaxCalculator('MH'), 'KA'));
    }

    public function test_international_orders_pay_no_tax(): void
    {
        $this->assertSame([], $this->lines(new TaxCalculator('MH'), 'INTL'));
    }

    public function test_seller_state_and_rate_can_be_changed(): void
    {
        $this->assertSame(['IGST 12%' => 12000], $this->lines(new TaxCalculator('KA', 1200), 'MH'));
    }
}
