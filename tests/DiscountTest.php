<?php

use Invoice\BulkDiscount;
use Invoice\Coupons\CouponRegistry;
use Invoice\CustomerDiscount;
use PHPUnit\Framework\TestCase;

class DiscountTest extends TestCase
{
    public function test_bulk_tiers_start_at_10_and_50_pieces(): void
    {
        $bulk = new BulkDiscount;

        $this->assertEquals(180, $bulk->lineTotal(20, 9));
        $this->assertEquals(190, $bulk->lineTotal(20, 10));
        $this->assertEquals(931, $bulk->lineTotal(20, 49));
        $this->assertEquals(900, $bulk->lineTotal(20, 50));
    }

    public function test_customer_discount_by_type(): void
    {
        $discount = new CustomerDiscount;

        $this->assertEquals(0, $discount->amount('retail', 1000));
        $this->assertEquals(80, $discount->amount('wholesale', 1000));
        $this->assertEquals(120, $discount->amount('vip', 1000));
        $this->assertEquals(0, $discount->amount('something-new', 1000));
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

        $this->assertEquals(90, $coupon->amount($first, 1000, 900));
        $this->assertEquals(0, $coupon->amount(['customer' => ['type' => 'retail', 'first_order' => false]], 1000, 900));
        $this->assertEquals(0, $coupon->amount(['customer' => ['type' => 'vip', 'first_order' => true]], 1000, 900));
    }

    public function test_flat_coupon_needs_a_subtotal_of_2000(): void
    {
        $coupon = (new CouponRegistry)->find('FLAT200');

        $this->assertEquals(200, $coupon->amount([], 2000, 2000));
        $this->assertEquals(0, $coupon->amount([], 1999.99, 1999.99));
    }

    public function test_only_the_freeship_coupon_waives_shipping(): void
    {
        $registry = new CouponRegistry;

        $this->assertTrue($registry->find('FREESHIP')->freeShipping());
        $this->assertFalse($registry->find('FLAT200')->freeShipping());
        $this->assertFalse($registry->find('WELCOME10')->freeShipping());
    }
}
