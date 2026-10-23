<?php

namespace Invoice;

class ShippingCalculator
{
    private const LOCAL_STATES = ['MH', 'GJ'];

    // zone => [first kg, each extra kg]
    private const RATES = [1 => [50, 10], 2 => [90, 18], 3 => [900, 250]];

    private const FREE_SHIPPING_FROM = 5000;

    public function zone(string $shipTo): int
    {
        if (in_array($shipTo, self::LOCAL_STATES)) {
            return 1;
        }

        return $shipTo == 'INTL' ? 3 : 2;
    }

    public function cost(string $shipTo, float $weightKg, float $payable, bool $freeShippingCoupon): float
    {
        $zone = $this->zone($shipTo);
        $kg = max(1, ceil($weightKg));

        [$firstKg, $extraKg] = self::RATES[$zone];
        $cost = $firstKg + ($kg - 1) * $extraKg;

        $domestic = $zone != 3;

        if ($domestic && ($freeShippingCoupon || $payable >= self::FREE_SHIPPING_FROM)) {
            return 0;
        }

        return $cost;
    }
}
