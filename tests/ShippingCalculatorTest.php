<?php

use Invoice\ShippingCalculator;
use PHPUnit\Framework\TestCase;

class ShippingCalculatorTest extends TestCase
{
    private ShippingCalculator $shipping;

    protected function setUp(): void
    {
        $this->shipping = new ShippingCalculator;
    }

    public function test_zones(): void
    {
        $this->assertSame(1, $this->shipping->zone('MH'));
        $this->assertSame(1, $this->shipping->zone('GJ'));
        $this->assertSame(2, $this->shipping->zone('KA'));
        $this->assertSame(3, $this->shipping->zone('INTL'));
    }

    public function test_first_kilo_and_extra_kilos_are_priced_per_zone(): void
    {
        $this->assertEquals(50, $this->shipping->cost('MH', 1, 100, false));
        $this->assertEquals(70, $this->shipping->cost('MH', 3, 100, false));
        $this->assertEquals(90, $this->shipping->cost('KA', 1, 100, false));
        $this->assertEquals(126, $this->shipping->cost('KA', 3, 100, false));
        $this->assertEquals(1400, $this->shipping->cost('INTL', 3, 100, false));
    }

    public function test_weight_is_rounded_up_and_never_below_one_kilo(): void
    {
        $this->assertEquals(60, $this->shipping->cost('MH', 1.01, 100, false));
        $this->assertEquals(50, $this->shipping->cost('MH', 0.01, 100, false));
        $this->assertEquals(50, $this->shipping->cost('MH', 0, 100, false));
    }

    public function test_free_over_5000_but_not_for_international(): void
    {
        $this->assertEquals(0, $this->shipping->cost('KA', 2, 5000, false));
        $this->assertEquals(90 + 18, $this->shipping->cost('KA', 2, 4999.99, false));
        $this->assertEquals(900 + 250, $this->shipping->cost('INTL', 2, 9000, false));
    }

    public function test_free_shipping_coupon_works_domestically_only(): void
    {
        $this->assertEquals(0, $this->shipping->cost('KA', 2, 100, true));
        $this->assertEquals(1150, $this->shipping->cost('INTL', 2, 100, true));
    }
}
