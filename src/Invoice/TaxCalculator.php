<?php

namespace Invoice;

class TaxCalculator
{
    public function __construct(
        private string $sellerState = 'MH',
        private float $rate = 0.18,
    ) {
    }

    /** @return array<string, float> label => amount */
    public function lines(float $base, string $shipTo): array
    {
        if ($shipTo == 'INTL') {
            return [];
        }

        if ($shipTo == $this->sellerState) {
            $half = round($base * $this->rate / 2, 2);

            return [
                'CGST '.($this->rate * 50).'%' => $half,
                'SGST '.($this->rate * 50).'%' => $half,
            ];
        }

        return ['IGST '.($this->rate * 100).'%' => round($base * $this->rate, 2)];
    }
}
