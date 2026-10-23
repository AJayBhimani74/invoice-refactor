<?php
// invoice.php - prints an invoice for a web order

$GLOBALS['TAX'] = 0.18;
$GLOBALS['SELLER_STATE'] = 'MH';

const BULK_TIERS = [50 => 0.10, 10 => 0.05];
const CUSTOMER_DISCOUNTS = ['wholesale' => 0.08, 'vip' => 0.12];
const WELCOME_COUPON_RATE = 0.10;
const FLAT_COUPON_AMOUNT = 200;
const FLAT_COUPON_MINIMUM = 2000;
const FREE_SHIPPING_FROM = 5000;
const ZONE_LOCAL_STATES = ['MH', 'GJ'];
// zone => [first kg, each extra kg]
const SHIPPING_RATES = [1 => [50, 10], 2 => [90, 18], 3 => [900, 250]];

function calculate_invoice($order)
{
    global $TAX, $SELLER_STATE;

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
    $zone = 2;
    if (in_array($order['ship_to'], ZONE_LOCAL_STATES)) {
        $zone = 1;
    } else if ($order['ship_to'] == 'INTL') {
        $zone = 3;
    }
    $kg = ceil($weight);
    if ($kg < 1) {
        $kg = 1;
    }
    [$first_kg, $extra_kg] = SHIPPING_RATES[$zone];
    $ship = $first_kg + ($kg - 1) * $extra_kg;
    if ($freeship && $zone != 3) {
        $ship = 0;
    }
    if ($after >= FREE_SHIPPING_FROM && $zone != 3) {
        $ship = 0;
    }

    // tax
    $base = $after + $ship;
    $cgst = 0;
    $sgst = 0;
    $igst = 0;
    $tax_lines = [];
    if ($order['ship_to'] != 'INTL') {
        if ($order['ship_to'] == $SELLER_STATE) {
            $cgst = round($base * $TAX / 2, 2);
            $sgst = round($base * $TAX / 2, 2);
            $tax_lines['CGST 9%'] = $cgst;
            $tax_lines['SGST 9%'] = $sgst;
        } else {
            $igst = round($base * $TAX, 2);
            $tax_lines['IGST 18%'] = $igst;
        }
    }

    $total = round($base + $cgst + $sgst + $igst, 2);

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
