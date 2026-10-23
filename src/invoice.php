<?php
// invoice.php - prints an invoice for a web order

const BULK_TIERS = [50 => 0.10, 10 => 0.05];
const CUSTOMER_DISCOUNTS = ['wholesale' => 0.08, 'vip' => 0.12];
const WELCOME_COUPON_RATE = 0.10;
const FLAT_COUPON_AMOUNT = 200;
const FLAT_COUPON_MINIMUM = 2000;

function calculate_invoice($order)
{
    $sub = 0;
    $weight = 0;
    $lines = [];
    foreach ($order['items'] as $i) {
        $line = $i['price'] * $i['qty'];
        foreach (BULK_TIERS as $from => $rate) {
            if ($i['qty'] >= $from) {
                $line = $line - $line * $rate;
                break;
            }
        }
        $line = round($line, 2);
        $sub = $sub + $line;
        $weight = $weight + $i['weight'] * $i['qty'];
        $lines[] = ['name' => $i['name'], 'qty' => $i['qty'], 'amount' => $line];
    }

    // customer discount
    $disc = 0;
    if (isset(CUSTOMER_DISCOUNTS[$order['customer']['type']])) {
        $disc = $sub * CUSTOMER_DISCOUNTS[$order['customer']['type']];
    }
    $after = $sub - $disc;

    // coupon
    $freeship = false;
    $cdisc = 0;
    if (isset($order['coupon']) && $order['coupon']) {
        $c = strtoupper(trim($order['coupon']));
        if ($c == 'WELCOME10') {
            if ($order['customer']['type'] == 'retail' && $order['customer']['first_order']) {
                $cdisc = $after * WELCOME_COUPON_RATE;
            }
        } else if ($c == 'FLAT200') {
            if ($sub >= FLAT_COUPON_MINIMUM) {
                $cdisc = FLAT_COUPON_AMOUNT;
            }
        } else if ($c == 'FREESHIP') {
            $freeship = true;
        }
    }
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
