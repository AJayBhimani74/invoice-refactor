<?php

namespace Invoice;

class TaxCalculator
{
    public function __construct(
        private string $sellerState = 'MH',
        private int $rateBasisPoints = 1800,
    ) {
    }

    /** @return array<string, Money> label => amount */
    public function lines(Money $base, string $shipTo): array
    {
        if ($shipTo == 'INTL') {
            return [];
        }

        if ($shipTo == $this->sellerState) {
            $half = $base->basisPoints(intdiv($this->rateBasisPoints, 2));
            $label = $this->percent($this->rateBasisPoints / 2);

            return ["CGST {$label}%" => $half, "SGST {$label}%" => $half];
        }

        return ['IGST '.$this->percent($this->rateBasisPoints).'%' => $base->basisPoints($this->rateBasisPoints)];
    }

    private function percent(float $basisPoints): string
    {
        return (string) ($basisPoints / 100);
    }
}
