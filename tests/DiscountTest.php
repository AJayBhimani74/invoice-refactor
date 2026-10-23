<?php

use Invoice\BulkDiscount;
use Invoice\Coupons\CouponRegistry;
use Invoice\CustomerDiscount;
use Invoice\Money;
use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase
{
    private function rupees(int $rupees): Money
    {
        return Money::fromRupees($rupees);
    }

    public function test_bulk_tiers_start_at_10_and_50_pieces(): void
    {
        $bulk = new BulkDiscount;

        $this->assertSame(18000, $bulk->lineTotal($this->rupees(20), 9)->paise);
        $this->assertSame(19000, $bulk->lineTotal($this->rupees(20), 10)->paise);
        $this->assertSame(93100, $bulk->lineTotal($this->rupees(20), 49)->paise);
        $this->assertSame(90000, $bulk->lineTotal($this->rupees(20), 50)->paise);
    }

    public function test_customer_discount_by_type(): void
    {
        $discount = new CustomerDiscount;

        $this->assertSame(0, $discount->amount('retail', $this->rupees(1000))->paise);
        $this->assertSame(8000, $discount->amount('wholesale', $this->rupees(1000))->paise);
        $this->assertSame(12000, $discount->amount('vip', $this->rupees(1000))->paise);
        $this->assertSame(0, $discount->amount('something-new', $this->rupees(1000))->paise);
    }

    public function test_registry_ignores_case_spaces_and_unknown_codes(): void
    {
        $registry = new CouponRegistry;

        $this->assertNotNull($registry->find(' welcome10 '));
        $this->assertNull($registry->find('SAVE50'));
        $this->assertNull($registry->find(''));
        $this->assertNull($registry->find(null));
    }

    public function test_welcome_coupon_is_for_first_orders_of_retail_customers(): void
    {
        $coupon = (new CouponRegistry)->find('WELCOME10');
        $first = ['customer' => ['type' => 'retail', 'first_order' => true]];

        $this->assertSame(9000, $coupon->amount($first, $this->rupees(1000), $this->rupees(900))->paise);
        $this->assertTrue($coupon->amount(['customer' => ['type' => 'retail', 'first_order' => false]], $this->rupees(1000), $this->rupees(900))->isZero());
        $this->assertTrue($coupon->amount(['customer' => ['type' => 'vip', 'first_order' => true]], $this->rupees(1000), $this->rupees(900))->isZero());
    }

    public function test_flat_coupon_needs_a_subtotal_of_2000(): void
    {
        $coupon = (new CouponRegistry)->find('FLAT200');

        $this->assertSame(20000, $coupon->amount([], $this->rupees(2000), $this->rupees(2000))->paise);
        $this->assertTrue($coupon->amount([], Money::fromPaise(199999), Money::fromPaise(199999))->isZero());
    }

    public function test_only_the_freeship_coupon_waives_shipping(): void
    {
        $registry = new CouponRegistry;

        $this->assertTrue($registry->find('FREESHIP')->freeShipping());
        $this->assertFalse($registry->find('FLAT200')->freeShipping());
        $this->assertFalse($registry->find('WELCOME10')->freeShipping());
    }
}
