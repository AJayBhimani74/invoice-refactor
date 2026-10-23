<?php

use Invoice\Money;
use Invoice\ShippingCalculator;
use PHPUnit\Framework\TestCase;

class ShippingCalculatorTest extends TestCase
{
    private ShippingCalculator $shipping;

    protected function setUp(): void
    {
        $this->shipping = new ShippingCalculator;
    }

    private function cost(string $shipTo, float $kg, float $payable, bool $coupon): int
    {
        return $this->shipping->cost($shipTo, $kg, Money::fromRupees($payable), $coupon)->paise;
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
        $this->assertSame(5000, $this->cost('MH', 1, 100, false));
        $this->assertSame(7000, $this->cost('MH', 3, 100, false));
        $this->assertSame(9000, $this->cost('KA', 1, 100, false));
        $this->assertSame(12600, $this->cost('KA', 3, 100, false));
        $this->assertSame(140000, $this->cost('INTL', 3, 100, false));
    }

    public function test_weight_is_rounded_up_and_never_below_one_kilo(): void
    {
        $this->assertSame(6000, $this->cost('MH', 1.01, 100, false));
        $this->assertSame(5000, $this->cost('MH', 0.01, 100, false));
        $this->assertSame(5000, $this->cost('MH', 0, 100, false));
    }

    public function test_free_over_5000_but_not_for_international(): void
    {
        $this->assertSame(0, $this->cost('KA', 2, 5000, false));
        $this->assertSame(10800, $this->cost('KA', 2, 4999.99, false));
        $this->assertSame(115000, $this->cost('INTL', 2, 9000, false));
    }

    public function test_free_shipping_coupon_works_domestically_only(): void
    {
        $this->assertSame(0, $this->cost('KA', 2, 100, true));
        $this->assertSame(115000, $this->cost('INTL', 2, 100, true));
    }
}
