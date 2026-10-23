<?php
// Kept so existing callers of build_invoice() keep working.

function build_invoice($order)
{
    $invoice = (new Invoice\InvoiceCalculator)->calculate($order);
    echo (new Invoice\InvoicePrinter)->render($invoice);

    return $invoice['total']->paise / 100.0;
}
