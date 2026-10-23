<?php
// invoice.php - prints an invoice for a web order

$TAX = 0.18;
$SELLER_STATE = 'MH';

function build_invoice($order)
{
    global $TAX, $SELLER_STATE;

    $sub = 0;
    $weight = 0;
    echo "INVOICE\n";
    echo "-------\n";
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
        echo str_pad($i['name'], 22) . str_pad($i['qty'], 5, ' ', STR_PAD_LEFT) . str_pad(number_format($line, 2, '.', ''), 12, ' ', STR_PAD_LEFT) . "\n";
    }
    echo "\n";
    echo "Subtotal: " . number_format($sub, 2, '.', '') . "\n";

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
    if ($disc > 0) {
        echo "Customer discount: -" . number_format($disc, 2, '.', '') . "\n";
    }
    if ($cdisc > 0) {
        echo "Coupon discount: -" . number_format($cdisc, 2, '.', '') . "\n";
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
    echo "Shipping: " . number_format($ship, 2, '.', '') . "\n";

    // tax
    $base = $after + $ship;
    $cgst = 0;
    $sgst = 0;
    $igst = 0;
    if ($order['ship_to'] != 'INTL') {
        if ($order['ship_to'] == $SELLER_STATE) {
            $cgst = round($base * $TAX / 2, 2);
            $sgst = round($base * $TAX / 2, 2);
            echo "CGST 9%: " . number_format($cgst, 2, '.', '') . "\n";
            echo "SGST 9%: " . number_format($sgst, 2, '.', '') . "\n";
        } else {
            $igst = round($base * $TAX, 2);
            echo "IGST 18%: " . number_format($igst, 2, '.', '') . "\n";
        }
    }

    $total = round($base + $cgst + $sgst + $igst, 2);
    echo "TOTAL: " . number_format($total, 2, '.', '') . "\n";

    return $total;
}
