<?php

namespace Invoice\Coupons;

class FlatCoupon implements Coupon
{
    private const AMOUNT = 200;

    private const MINIMUM_SUBTOTAL = 2000;

    public function amount(array $order, float $subtotal, float $payable): float
    {
        return $subtotal >= self::MINIMUM_SUBTOTAL ? self::AMOUNT : 0;
    }

    public function freeShipping(): bool
    {
        return false;
    }
}
