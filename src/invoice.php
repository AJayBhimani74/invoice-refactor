<?php
// invoice.php - prints an invoice for a web order

$GLOBALS['TAX'] = 0.18;
$GLOBALS['SELLER_STATE'] = 'MH';

function calculate_invoice($order)
{
    global $TAX, $SELLER_STATE;

    $sub = 0;
    $weight = 0;
    $lines = [];
    foreach ($order['items'] as $i) {
        $line = $i['price'] * $i['qty'];
        if ($i['qty'] >= 50) {
            $line = $line - $line * 0.10;
        } else if ($i['qty'] >= 10) {
            $line = $line - $line * 0.05;
        }
        $line = round($line, 2);
        $sub = $sub + $line;
        $weight = $weight + $i['weight'] * $i['qty'];
        $lines[] = ['name' => $i['name'], 'qty' => $i['qty'], 'amount' => $line];
    }

    // customer discount
    $disc = 0;
    if ($order['customer']['type'] == 'wholesale') {
        $disc = $sub * 0.08;
    } else if ($order['customer']['type'] == 'vip') {
        $disc = $sub * 0.12;
    }
    $after = $sub - $disc;

    // coupon
    $freeship = false;
    $cdisc = 0;
    if (isset($order['coupon']) && $order['coupon']) {
        $c = strtoupper(trim($order['coupon']));
        if ($c == 'WELCOME10') {
            if ($order['customer']['type'] == 'retail' && $order['customer']['first_order']) {
                $cdisc = $after * 0.10;
            }
        } else if ($c == 'FLAT200') {
            if ($sub >= 2000) {
                $cdisc = 200;
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
    if ($order['ship_to'] == 'MH' || $order['ship_to'] == 'GJ') {
        $zone = 1;
    } else if ($order['ship_to'] == 'INTL') {
        $zone = 3;
    }
    $kg = ceil($weight);
    if ($kg < 1) {
        $kg = 1;
    }
    if ($zone == 1) {
        $ship = 50 + ($kg - 1) * 10;
    } else if ($zone == 2) {
        $ship = 90 + ($kg - 1) * 18;
    } else {
        $ship = 900 + ($kg - 1) * 250;
    }
    if ($freeship && $zone != 3) {
        $ship = 0;
    }
    if ($after >= 5000 && $zone != 3) {
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
