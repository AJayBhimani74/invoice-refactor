<?php

namespace Invoice;

class InvoicePrinter
{
    public function render(array $invoice): string
    {
        $out = "INVOICE\n-------\n";

        foreach ($invoice['lines'] as $l) {
            $out .= str_pad($l['name'], 22).str_pad($l['qty'], 5, ' ', STR_PAD_LEFT).str_pad($l['amount']->format(), 12, ' ', STR_PAD_LEFT)."\n";
        }

        $out .= "\nSubtotal: ".$invoice['subtotal']->format()."\n";

        if ($invoice['customer_discount']->isPositive()) {
            $out .= 'Customer discount: -'.$invoice['customer_discount']->format()."\n";
        }

        if ($invoice['coupon_discount']->isPositive()) {
            $out .= 'Coupon discount: -'.$invoice['coupon_discount']->format()."\n";
        }

        $out .= 'Shipping: '.$invoice['shipping']->format()."\n";

        foreach ($invoice['tax_lines'] as $label => $amount) {
            $out .= $label.': '.$amount->format()."\n";
        }

        return $out.'TOTAL: '.$invoice['total']->format()."\n";
    }
}
