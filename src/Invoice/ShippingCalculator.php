<?php

namespace Invoice;

class ShippingCalculator
{
    private const LOCAL_STATES = ['MH', 'GJ'];

    // zone => [first kg, each extra kg] in rupees
    private const RATES = [1 => [50, 10], 2 => [90, 18], 3 => [900, 250]];

    private const FREE_SHIPPING_FROM = 5000;

    public function zone(string $shipTo): int
    {
        if (in_array($shipTo, self::LOCAL_STATES)) {
            return 1;
        }

        return $shipTo == 'INTL' ? 3 : 2;
    }

    public function cost(string $shipTo, float $weightKg, Money $payable, bool $freeShippingCoupon): Money
    {
        $zone = $this->zone($shipTo);
        $kg = (int) max(1, ceil($weightKg));

        [$firstKg, $extraKg] = self::RATES[$zone];

        $domestic = $zone != 3;
        $free = $freeShippingCoupon || $payable->isAtLeast(Money::fromRupees(self::FREE_SHIPPING_FROM));

        if ($domestic && $free) {
            return Money::zero();
        }

        return Money::fromRupees($firstKg + ($kg - 1) * $extraKg);
    }
}
