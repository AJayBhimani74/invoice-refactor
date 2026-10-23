<?php

namespace Invoice;

class InvoicePrinter
{
    public function render(array $invoice): string
    {
        $out = "INVOICE\n-------\n";

        foreach ($invoice['lines'] as $l) {
            $out .= str_pad($l['name'], 22).str_pad($l['qty'], 5, ' ', STR_PAD_LEFT).str_pad($this->money($l['amount']), 12, ' ', STR_PAD_LEFT)."\n";
        }

        $out .= "\nSubtotal: ".$this->money($invoice['subtotal'])."\n";

        if ($invoice['customer_discount'] > 0) {
            $out .= 'Customer discount: -'.$this->money($invoice['customer_discount'])."\n";
        }

        if ($invoice['coupon_discount'] > 0) {
            $out .= 'Coupon discount: -'.$this->money($invoice['coupon_discount'])."\n";
        }

        $out .= 'Shipping: '.$this->money($invoice['shipping'])."\n";

        foreach ($invoice['tax_lines'] as $label => $amount) {
            $out .= $label.': '.$this->money($amount)."\n";
        }

        return $out.'TOTAL: '.$this->money($invoice['total'])."\n";
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
