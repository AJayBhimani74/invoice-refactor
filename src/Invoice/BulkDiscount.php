<?php

namespace Invoice;

class BulkDiscount
{
    // from this quantity on => basis points taken off the line
    private const TIERS = [50 => 1000, 10 => 500];

    public function lineTotal(Money $price, int $qty): Money
    {
        $line = $price->times($qty);

        foreach (self::TIERS as $from => $points) {
            if ($qty >= $from) {
                return $line->basisPoints(10000 - $points);
            }
        }

        return $line;
    }
}
