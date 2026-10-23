<?php

namespace Invoice\Coupons;

class WelcomeCoupon implements Coupon
{
    public function amount(array $order, float $subtotal, float $payable): float
    {
        $customer = $order['customer'];

        if ($customer['type'] == 'retail' && $customer['first_order']) {
            return $payable * 0.10;
        }

        return 0;
    }

    public function freeShipping(): bool
    {
        return false;
    }
}
