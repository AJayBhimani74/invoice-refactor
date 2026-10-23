<?php

namespace Invoice\Coupons;

interface Coupon
{
    /**
     * @param  float  $subtotal  sum of the lines
     * @param  float  $payable  subtotal after the customer discount
     */
    public function amount(array $order, float $subtotal, float $payable): float;

    public function freeShipping(): bool;
}
