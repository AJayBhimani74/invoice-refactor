<?php

namespace Invoice\Coupons;

use Invoice\Money;

class FreeShippingCoupon implements Coupon
{
    public function amount(array $order, Money $subtotal, Money $payable): Money
    {
        return Money::zero();
    }

    public function freeShipping(): bool
    {
        return true;
    }
}
