<?php

namespace Invoice\Coupons;

use Invoice\Money;

class FlatCoupon implements Coupon
{
    public function amount(array $order, Money $subtotal, Money $payable): Money
    {
        return $subtotal->isAtLeast(Money::fromRupees(2000)) ? Money::fromRupees(200) : Money::zero();
    }

    public function freeShipping(): bool
    {
        return false;
    }
}
