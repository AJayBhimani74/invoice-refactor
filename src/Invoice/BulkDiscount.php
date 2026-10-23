<?php

namespace Invoice;

class BulkDiscount
{
    // from this quantity on => share taken off the line
    private const TIERS = [50 => 0.10, 10 => 0.05];

    public function lineTotal(float $price, int $qty): float
    {
        $line = $price * $qty;

        foreach (self::TIERS as $from => $rate) {
            if ($qty >= $from) {
                return $line - $line * $rate;
            }
        }

        return $line;
    }
}
