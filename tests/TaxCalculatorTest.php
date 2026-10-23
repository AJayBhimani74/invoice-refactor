<?php

use Invoice\TaxCalculator;
use PHPUnit\Framework\TestCase;

class TaxCalculatorTest extends TestCase
{
    public function test_same_state_splits_into_cgst_and_sgst(): void
    {
        $this->assertSame(
            ['CGST 9%' => 90.0, 'SGST 9%' => 90.0],
            (new TaxCalculator('MH'))->lines(1000, 'MH')
        );
    }

    public function test_other_state_pays_igst(): void
    {
        $this->assertSame(['IGST 18%' => 180.0], (new TaxCalculator('MH'))->lines(1000, 'KA'));
    }

    public function test_international_orders_pay_no_tax(): void
    {
        $this->assertSame([], (new TaxCalculator('MH'))->lines(1000, 'INTL'));
    }

    public function test_seller_state_and_rate_can_be_changed(): void
    {
        $this->assertSame(['IGST 12%' => 120.0], (new TaxCalculator('KA', 0.12))->lines(1000, 'MH'));
    }
}
