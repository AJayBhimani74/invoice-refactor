<?php
// invoice.php - prints an invoice for a web order

function calculate_invoice($order)
{
    $sub = 0;
    $weight = 0;
    $lines = [];
    foreach ($order['items'] as $i) {
        $line = round((new Invoice\BulkDiscount)->lineTotal($i['price'], $i['qty']), 2);
        $sub = $sub + $line;
        $weight = $weight + $i['weight'] * $i['qty'];
        $lines[] = ['name' => $i['name'], 'qty' => $i['qty'], 'amount' => $line];
    }

    // customer discount
    $disc = (new Invoice\CustomerDiscount)->amount($order['customer']['type'], $sub);
    $after = $sub - $disc;

    // coupon
    $coupon = (new Invoice\Coupons\CouponRegistry)->find($order['coupon'] ?? null);
    $cdisc = $coupon ? $coupon->amount($order, $sub, $after) : 0;
    $freeship = $coupon ? $coupon->freeShipping() : false;
    $after = $after - $cdisc;
    if ($after < 0) {
        $after = 0;
    }

    // shipping
    $ship = (new Invoice\ShippingCalculator)->cost($order['ship_to'], $weight, $after, $freeship);

    // tax
    $base = $after + $ship;
    $tax_lines = (new Invoice\TaxCalculator)->lines($base, $order['ship_to']);

    $total = round($base + array_sum($tax_lines), 2);

    return [
        'lines' => $lines,
        'subtotal' => $sub,
        'customer_discount' => $disc,
        'coupon_discount' => $cdisc,
        'shipping' => $ship,
        'tax_lines' => $tax_lines,
        'total' => $total,
    ];
}

function print_invoice($invoice)
{
    echo "INVOICE\n";
    echo "-------\n";
    foreach ($invoice['lines'] as $l) {
        echo str_pad($l['name'], 22) . str_pad($l['qty'], 5, ' ', STR_PAD_LEFT) . str_pad(number_format($l['amount'], 2, '.', ''), 12, ' ', STR_PAD_LEFT) . "\n";
    }
    echo "\n";
    echo "Subtotal: " . number_format($invoice['subtotal'], 2, '.', '') . "\n";
    if ($invoice['customer_discount'] > 0) {
        echo "Customer discount: -" . number_format($invoice['customer_discount'], 2, '.', '') . "\n";
    }
    if ($invoice['coupon_discount'] > 0) {
        echo "Coupon discount: -" . number_format($invoice['coupon_discount'], 2, '.', '') . "\n";
    }
    echo "Shipping: " . number_format($invoice['shipping'], 2, '.', '') . "\n";
    foreach ($invoice['tax_lines'] as $label => $amount) {
        echo $label . ": " . number_format($amount, 2, '.', '') . "\n";
    }
    echo "TOTAL: " . number_format($invoice['total'], 2, '.', '') . "\n";
}

function build_invoice($order)
{
    $invoice = calculate_invoice($order);
    print_invoice($invoice);

    return $invoice['total'];
}
