<?php

namespace Invoice;

class CustomerDiscount
{
    // basis points
    private const RATES = ['wholesale' => 800, 'vip' => 1200];

    public function amount(string $customerType, Money $subtotal): Money
    {
        return $subtotal->basisPoints(self::RATES[$customerType] ?? 0);
    }
}
