<?php

namespace Invoice\Coupons;

use Invoice\Money;

interface Coupon
{
    /**
     * @param  Money  $subtotal  sum of the lines
     * @param  Money  $payable  subtotal after the customer discount
     */
    public function amount(array $order, Money $subtotal, Money $payable): Money;

    public function freeShipping(): bool;
}
