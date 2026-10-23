<?php

namespace Invoice\Coupons;

use Invoice\Money;

class WelcomeCoupon implements Coupon
{
    public function amount(array $order, Money $subtotal, Money $payable): Money
    {
        $customer = $order['customer'];

        if ($customer['type'] == 'retail' && $customer['first_order']) {
            return $payable->basisPoints(1000);
        }

        return Money::zero();
    }

    public function freeShipping(): bool
    {
        return false;
    }
}
