<?php

namespace Invoice;

class CustomerDiscount
{
    private const RATES = ['wholesale' => 0.08, 'vip' => 0.12];

    public function amount(string $customerType, float $subtotal): float
    {
        return $subtotal * (self::RATES[$customerType] ?? 0);
    }
}
