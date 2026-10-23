<?php

namespace Invoice\Coupons;

class FreeShippingCoupon implements Coupon
{
    public function amount(array $order, float $subtotal, float $payable): float
    {
        return 0;
    }

    public function freeShipping(): bool
    {
        return true;
    }
}
